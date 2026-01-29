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
import { router } from '@inertiajs/vue3'

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

function getBaseYear(sheet) {
  const cell = sheet["C4"];
  if (!cell || cell.v == null) return dayjs().year();

  // C4 は「年」そのもの
  if (typeof cell.v === "number" && cell.v >= 1900 && cell.v <= 2100) {
    return cell.v;
  }

  // 念のため文字列年
  if (typeof cell.v === "string" && /^\d{4}$/.test(cell.v)) {
    return Number(cell.v);
  }

  // フォールバック（使われないはず）
  return dayjs().year();
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

    console.log("C4:", sheet["C4"])
console.log("C4.v:", sheet["C4"]?.v, typeof sheet["C4"]?.v)

console.log("D6:", sheet["D6"])
console.log("D6.v:", sheet["D6"]?.v, typeof sheet["D6"]?.v)

    const servingCols = ["D", "M", "V", "AE", "AN", "AW", "BF"];
    const servingDates = {};

const baseYear = sheet["C4"]?.v; // 2025

let year = baseYear;
let lastMonth = null;

servingCols.forEach((col) => {
  const cell = sheet[`${col}6`];

  if (!cell || typeof cell.v !== "string") {
    servingDates[col] = null;
    return;
  }

  // "12/29(月)" → "12/29"
  const md = cell.v.replace(/\(.+\)/, "").trim();
  const [month, day] = md.split("/").map(Number);

  if (!month || !day) {
    servingDates[col] = null;
    return;
  }

  // 年跨ぎ（12 → 1）
  if (lastMonth !== null && month < lastMonth) {
    year++;
  }
  lastMonth = month;

  servingDates[col] =
    `${year}-${String(month).padStart(2, "0")}-${String(day).padStart(2, "0")}`;
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

//        const dishName = `${mealType} ${menuCell.v.toString().trim()}`;
        const dishName = `${menuCell.v.toString().trim()}`;
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

// 保存（サーバーに POST）
async function saveMenus() {
  try {
    const res = await axios.post('/menus/import', { menus: menusPreview.value }, {
        withCredentials: true, // ← これが重要
        headers: { 'Content-Type': 'application/json' }
    })
    alert(res.data.message)
    router.visit('/menus/import')

  } catch (e) {
    console.error(e)
    alert('保存に失敗しました')
    router.visit('/menus/import')
  }

}

</script>









