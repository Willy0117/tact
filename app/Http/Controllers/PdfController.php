<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

use TCPDF_FONTS;
use setasign\Fpdi\Tcpdf\Fpdi;

use App\Models\Temperature; 
use App\Models\Tenant;
use App\Models\User;
use App\Models\Process;
use App\Models\Menu;
use App\Models\Device;
use App\Models\Sensor;
use App\Models\Operator;

use Carbon\Carbon;

use Illuminate\Validation\Rule;

class PdfController extends Controller
{

    public function generate(Request $request)
    {

        $user = $request->user();
        $query = Temperature::query();

        // テナント制限
        if (! $user->hasRole('Super Admin')) {
            $query->where('tenant_id', $user->tenant_id);
        }

        /*
        |--------------------------------------------------------------------------
        | 検索条件（URLクエリから取得）
        |--------------------------------------------------------------------------
        */
        $menuId     = $request->query('menu_id');
        $sensorId   = $request->query('sensor_id');
        $deviceId   = $request->query('device_id');
        $operatorId = $request->query('operator_id');
        $handyNo    = $request->query('handy_no');
        $processId  = $request->query('process_id');

        if ($menuId) {
            $query->where('menu_id', $menuId);
        }
        if ($sensorId) {
            $query->where('sensor_id', $sensorId);
        }
        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }
        if ($operatorId) {
            $query->where('operator_id', $operatorId);
        }
        if ($handyNo) {
            $query->where('handy_no', $handyNo);
        }
        if ($processId) {
            $query->where('process_id', $processId);
        }

        // 日付絞り込み（デフォルトは今日）
        $dateFrom = $request->query('date_from', Carbon::today()->toDateString());
        $dateTo   = $request->query('date_to', Carbon::today()->toDateString());

        $dateType = $request->query('date_type', 'serving'); // デフォルト献立日

        if ($dateType === 'serving') {
            $query->whereHas('menu', function ($q) use ($dateFrom, $dateTo) {
                $q->when($dateFrom, fn($q) => $q->where('serving_date', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->where('serving_date', '<=', $dateTo));
            });
        } else {
            $query->when($dateFrom, fn($q) => $q->whereDate('temperature_logs.created_at', '>=', $dateFrom))
                ->when($dateTo, fn($q) => $q->whereDate('temperature_logs.created_at', '<=', $dateTo));
        }

        /*
        |--------------------------------------------------------------------------
        | ソート
        |--------------------------------------------------------------------------
        */
        $allowedSorts = [
            'menu_date',
            'menu_id',
            'device_id',
            'sensor_id',
            'operator_id',
            'handy_no',
            'process_id',
            'created_at',
        ];

        $sort = $request->query('sort_by', 'created_at');
        $dir  = $request->query('sort_dir') === 'asc' ? 'asc' : 'desc';

        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'created_at';
        }

        if ($sort === 'menu_date') {
            if ($dateType === 'serving') {
                // 配膳日 + 配膳時間
                $query->orderBy(
                    Menu::selectRaw("TIMESTAMP(serving_date, COALESCE(serving_time, '00:00:00'))")
                        ->whereColumn('menus.id', 'temperature_logs.menu_id'),
                    $dir
                );
            } else {
                $query->orderBy('temperature_logs.created_at', $dir);
            }
            // 安定ソート
            $query->orderBy('temperature_logs.created_at', 'desc');
        } else {
            $query->orderBy($sort, $dir);
        }

        $logs = $query->with(['menu', 'sensor', 'device', 'operator', 'process'])
            ->get();

                // FPDI + TCPDF
        $pdf = new Fpdi();
        $pdf->SetMargins(0, 0, 0);
        $pdf->SetAutoPageBreak(false);
        $pdf->setCellPaddings(0, 0, 0, 0); 
        // これを追加：デフォルトのヘッダー（線とタイトル）を非表示にする
        $pdf->setPrintHeader(false); 
        // ついでにフッター（ページ番号など）も不要ならオフにする
        $pdf->setPrintFooter(false); 
        // 2. A4横（Landscape）でページ追加
        $pdf->AddPage('L', 'A4');
        // 既存PDFテンプレート読み込み
        $templatePath = storage_path('app/templates/result.pdf');
        $pageCount = $pdf->setSourceFile($templatePath);
        $tpl = $pdf->importPage(1);
        $pdf->useTemplate($tpl, 0, 0, 297, 210);

        // TCPDF同梱の日本語フォント
        $pdf->SetFont('kozminproregular', '', 7); // もしくは cid0jp
        // --- データ書き込み開始 ---
        $y = 44;          // 最初の行のY位置（テンプレートに合わせて調整）
        $rowHeight = 4.85;   // 1行の高さ（テンプレートの行間と同じにする）

        $pdf->SetXY(246, 21);
        $pdf->Cell(20, $rowHeight, NOW() , 0, 0, 'L');

        $pdf->SetXY(37, 25);
        $pdf->Cell(20, $rowHeight, $dateFrom . ' 〜 ' . $dateTo , 0, 0, 'L');

        foreach ($logs as $log) {
            // ページ末尾まで行ったら改ページしてテンプレートを再配置
            if ($y > 185) { 
                $pdf->AddPage('L', 'A4');
                $pdf->useTemplate($tpl, 0, 0, 297, 210);
                $pdf->SetXY(246, 21);
                $pdf->Cell(20, $rowHeight, NOW() , 0, 0, 'L');

                $pdf->SetXY(37, 25);
                $pdf->Cell(20, $rowHeight, $dateFrom . ' 〜 ' . $dateTo , 0, 0, 'L');
                $y = 44; // 開始位置に戻す
            }

            // 各セルの書き込み（X座標, Y座標）
            // Cell(幅, 高さ, 文字列, 枠線[0=なし], 改行[0], 揃え['L'/'C'/'R'])
            
            $pdf->SetXY(223, $y); 
            $pdf->Cell(40, $rowHeight, $log->created_at->format('Y-m-d H:i'), 0, 0, 'L');

            $pdf->SetXY(7, $y); 
            $pdf->Cell(40, $rowHeight, $log->menu?->serving_date->format('Y-m-d'), 0, 0, 'L');
            $pdf->SetXY(27, $y); 
            $pdf->Cell(40, $rowHeight, \Carbon\Carbon::parse($log->menu?->serving_time)->format('H:i'), 0, 0, 'L');

            $pdf->SetX(37); // 横位置をずらす
            $pdf->Cell(80, $rowHeight, $log->menu?->name, 0, 0, 'L');

            $pdf->SetX(65); // 横位置をずらす
            $pdf->Cell(80, $rowHeight, $log->process?->name, 0, 0, 'L');

            $pdf->SetX(75); // 横位置をずらす
            $pdf->Cell(80, $rowHeight, $log->device?->name, 0, 0, 'L');

            $pdf->SetX(97); // 横位置をずらす
            $pdf->Cell(80, $rowHeight, $log->operator?->name, 0, 0, 'L');
            
            $pdf->SetX(119); // 横位置をずらす
            $pdf->Cell(80, $rowHeight, $log->sensor?->name ?? '-', 0, 0, 'L');

            $currentX = 131; // 開始位置

            if (!empty($log->temperatures) && count($log->temperatures) > 0) {
                foreach($log->temperatures as $temp) {
                    $pdf->SetX($currentX);
                    // $temp が配列の場合は $temp['value']、オブジェクトの場合は $temp->value
                    $val = is_array($temp) ? ($temp['value'] ?? '') : ($temp->value ?? '');
                    
                    $pdf->Cell(20, $rowHeight, $val . '', 0, 0, 'R');
                    $currentX += 14; 
                }
            } else {
                $pdf->SetX($currentX);
                $pdf->Cell(20, $rowHeight, '-', 0, 0, 'R'); 
            }

            $pdf->SetX(246);
            $note = mb_strimwidth($log->note ?? '', 0, 37, '...', 'UTF-8');
            $pdf->Cell(20, $rowHeight, $note , 0, 0, 'L');

            // 1行分下に移動
            $y += $rowHeight;
        }







 
        $pdf->Output('temperature_report.pdf', 'I');

        exit;
    }

}


