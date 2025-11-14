<template>
  <AppLayout>
    <template #header>{{ t('import_menu') }}</template>

    <div class="p-6 space-y-4">

      <!-- ファイル選択 / Drag&Drop -->
      <div
        @drop.prevent="handleDrop"
        @dragover.prevent
        class="border-dashed border-2 p-6 rounded text-center cursor-pointer hover:bg-gray-50"
      >
        <input
          type="file"
          @change="handleFile"
          ref="fileInput"
          accept=".xlsx,.xls"
          class="hidden"
        />
        <p class="text-gray-500">
          {{ t('drag_drop_or_click') }}
        </p>
        <button
          @click="$refs.fileInput.click()"
          class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
        >
          {{ t('select_file') }}
        </button>
      </div>

      <!-- プレビュー表示 -->
      <div v-if="menusPreview.length">
        <h2 class="font-semibold mb-2">{{ t('preview') }}</h2>
        <table border="1">
            <thead>
                <tr>
                <th>食事</th>
                <th v-for="date in menusTable.dates" :key="date">{{ date }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="row in menusTable.table" :key="row.mealType">
                <td>{{ row.mealType }}</td>
                <td v-for="date in menusTable.dates" :key="date">{{ row[date] }}</td>
                </tr>
            </tbody>
        </table>

        <button
          @click="saveMenus"
          class="mt-2 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700"
        >
          {{ t('save') }}
        </button>
      </div>

      <!-- 成功 / エラー -->
      <div v-if="successMessage" class="text-green-600 font-semibold">
        {{ successMessage }}
      </div>
      <div v-if="errorMessage" class="text-red-600 font-semibold">
        {{ errorMessage }}
      </div>

    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { ref, computed} from 'vue'
import { useI18n } from 'vue-i18n'
import * as XLSX from 'xlsx'
import dayjs from 'dayjs'
import axios from 'axios'

// CSRF Cookie 取得・送信を有効化
axios.defaults.withCredentials = true

const { t } = useI18n()

const file = ref(null)
const fileInput = ref(null)
const servingDates = ref([])
const menusPreview = ref([])
const successMessage = ref('')
const errorMessage = ref('')
const isSaving = ref(false)
// ファイル選択
const handleFile = (event) => {
  file.value = event.target.files[0]
  if (!file.value) return
  parseExcel(file.value)
}

// Drag & Drop
const handleDrop = (event) => {
  file.value = event.dataTransfer.files[0]
  if (!file.value) return
  parseExcel(file.value)
}

// Excelシリアル値 → JS Date
function excelDateToJSDate(serial) {
  const utc_days = serial - 25569
  const utc_value = utc_days * 86400
  return new Date(utc_value * 1000)
}

const menusTable = computed(() => {
  if (!menusPreview.value.length) return { dates: [], table: [] }

  const dates = [...new Set(menusPreview.value.map(m => m.serving_date))].sort()
  const mealTypes = ["朝食","おやつ(10)","昼食","おやつ(15)","夕食"]

  const table = mealTypes.map(mealType => {
    const row = { mealType }
    dates.forEach(date => {
      const items = menusPreview.value
        .filter(m => m.serving_date === date && m.serving_time === mealTimeMap(mealType))
        .map(m => {
          // 調理日を追加
          if (m.cooking_date && m.cooking_date !== m.serving_date) {
            return `${m.dish_name} (${m.cooking_date})`
          }
          return m.dish_name
        })
        .join(", ")
      row[date] = items || ""
    })
    return row
  })

  return { dates, table }
})

function mealTimeMap(mealType){
  if(mealType === "朝食") return "08:00:00"
  if(mealType === "おやつ(10)") return "10:00:00"
  if(mealType === "昼食") return "12:00:00"
  if(mealType === "おやつ(15)") return "15:00:00"
  if(mealType === "夕食") return "18:00:00"
  return "00:00:00"
}

function parseExcel(fileObj) {
  const reader = new FileReader();
  reader.onload = (e) => {
    const data = new Uint8Array(e.target.result);
    const workbook = XLSX.read(data, { type: "array" });
    const sheet = workbook.Sheets[workbook.SheetNames[0]];

    const servingCols = ["D", "M", "V", "AE", "AN", "AW", "BF"];
    const servingDates = {};

    servingCols.forEach((col) => {
      const cell = sheet[`${col}6`];
      if (cell && cell.v) {
        const str = cell.v.toString().replace(/\(.+\)/, "");
        const parsed = dayjs(str, ["M/D", "MM/DD"]);
        servingDates[col] = parsed.isValid() ? parsed.format("YYYY-MM-DD") : null;
      } else {
        servingDates[col] = null;
      }
    });

    const cookingMap = {
      D: "K",
      M: "T",
      V: "AC",
      AE: "AL",
      AN: "AU",
      AW: "BD",
      BF: "BM",
    };

    const tempMenus = [];

    Object.keys(sheet).forEach((cellAddr) => {
      if (!cellAddr.startsWith("B")) return;
      const row = parseInt(cellAddr.replace("B", ""));
      if (row < 7) return;

      const mealType = sheet[cellAddr]?.v?.toString().trim();
      if (!mealType) return;

      servingCols.forEach((col) => {
        const menuCell = sheet[`${col}${row}`];
        if (!menuCell || !menuCell.v) return;

        const dishName = `${mealType} ${menuCell.v.toString().trim()}`;
        const servingDate = servingDates[col];
        if (!servingDate) return;

        const cookingCol = cookingMap[col];
        const cookingCell = sheet[`${cookingCol}${row}`];
        let cookingDate = null;
        if (cookingCell && cookingCell.v) {
          const val = cookingCell.v;
          if (typeof val === "number") {
            const d = XLSX.SSF.parse_date_code(val);
            if (d) cookingDate = dayjs(new Date(d.y, d.m - 1, d.d)).format("YYYY-MM-DD");
          } else {
            const parsed = dayjs(val);
            if (parsed.isValid()) cookingDate = parsed.format("YYYY-MM-DD");
          }
        }
        if (!cookingDate || cookingDate === servingDate) cookingDate = null;

        let servingTime = "00:00:00";
        if (/おやつ\((\d+)\)/u.test(mealType)) {
          const m = mealType.match(/おやつ\((\d+)\)/u);
          servingTime = `${m[1].padStart(2, "0")}:00:00`;
        } else if (mealType.includes("朝")) {
          servingTime = "08:00:00";
        } else if (mealType.includes("昼")) {
          servingTime = "12:00:00";
        } else if (mealType.includes("夕")) {
          servingTime = "18:00:00";
        }

        tempMenus.push({
          dish_name: dishName,
          serving_date: servingDate,
          serving_time: servingTime,
          cooking_date: cookingDate,
        });
      });
    });

    menusPreview.value = tempMenus;
  };

  reader.readAsArrayBuffer(fileObj);
}


// Excel解析してプレビュー作成
/*
function parseExcel(fileObj) {
  const reader = new FileReader()
  reader.onload = (e) => {
    const data = new Uint8Array(e.target.result)
    const workbook = XLSX.read(data, { type: 'array' })
    const sheet = workbook.Sheets[workbook.SheetNames[0]]

    // 年取得（C4）
    const yearCell = sheet['C4']?.v
    const year = parseInt(yearCell) || dayjs().year()

    // 配膳日セル D6〜BF6
    const servingCells = ['D6','M6','V6','AE6','AN6','AW6','BF6']
    servingDates.value = servingCells.map(cell => {
      const val = sheet[cell]?.v
      if (!val) return null
      const str = val.toString().split('(')[0]  // おやつ(10)除去
      const [month, day] = str.split('/').map(Number)
      if (!month || !day) return null
      return dayjs(`${year}-${month}-${day}`).format('YYYY-MM-DD')
    }).filter(Boolean)

    // 献立行マッピング
    const mealRows = [
      { type: '朝食', start: 6, end: 11, time: '08:00:00' },
      { type: 'おやつ(10)', start: 12, end: 14, time: '10:00:00' },
      { type: '昼食', start: 15, end: 21, time: '12:00:00' },
      { type: 'おやつ(15)', start: 22, end: 24, time: '15:00:00' },
      { type: '夕食', start: 25, end: 31, time: '18:00:00' },
    ]

    const offsetCooking = 10 - 3 // K列-D列

    menusPreview.value = []

    mealRows.forEach(meal => {
      for (let r = meal.start; r <= meal.end; r++) {
        servingCells.forEach((cell, i) => {
          const col = XLSX.utils.decode_cell(cell).c
          const dishCell = sheet[XLSX.utils.encode_cell({ c: col, r })]?.v
          if (!dishCell) return

          const servingDate = servingDates.value[i]
          if (!servingDate) return

          const cookingCol = col + offsetCooking
          const cookingCellRaw = sheet[XLSX.utils.encode_cell({ c: cookingCol, r })]?.v
          let cookingDate = null

          if (cookingCellRaw !== undefined && cookingCellRaw !== null && cookingCellRaw !== '') {
            if (typeof cookingCellRaw === 'number') {
              cookingDate = dayjs(excelDateToJSDate(cookingCellRaw)).format('YYYY-MM-DD')
            } else if (cookingCellRaw instanceof Date) {
              cookingDate = dayjs(cookingCellRaw).format('YYYY-MM-DD')
            } else {
              const parsed = dayjs(cookingCellRaw)
              if (parsed.isValid()) cookingDate = parsed.format('YYYY-MM-DD')
            }
          }

          // cooking_date が null または serving_date と同じ場合は null
          if (!cookingDate || cookingDate === servingDate) cookingDate = null

          menusPreview.value.push({
            dish_name: dishCell,
            serving_date: servingDate,
            serving_time: meal.time,
            cooking_date: cookingDate,
          })
        })
      }
    })
  }
  reader.readAsArrayBuffer(fileObj)
}
*/
// 保存（サーバーに POST）
async function saveMenus() {
  try {
    const res = await axios.post('/menus/import', { menus: menusPreview.value }, {
        withCredentials: true, // ← これが重要
        headers: { 'Content-Type': 'application/json' }
    })
    alert(res.data.message)
  } catch (e) {
    console.error(e)
    alert('保存に失敗しました')
  }
}
/*
async function saveMenus() {
  if (!file.value) {
    alert(t('please_select_file'))
    return
  }

  const formData = new FormData()
  formData.append('file', file.value)

  isSaving.value = true
  successMessage.value = ''
  errorMessage.value = ''

  try {
    const response = await axios.post('/menus/import', formData, {
      withCredentials: true,
      headers: { 'Content-Type': 'multipart/form-data' }
    })

    successMessage.value = t('success_saved')
    menusPreview.value = []
    file.value = null
  } catch (err) {
    console.error(err)
    errorMessage.value = t('error_save')
  } finally {
    isSaving.value = false
  }
}
*/
</script>









