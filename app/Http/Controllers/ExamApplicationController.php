<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ExamApplicationController extends Controller
{
    public function index()
    {
        // モックデータ
        $applications = [
            ['id' => 1, 'name' => '山田 太郎', 'email' => 'yamada@example.com', 'submitted_at' => '2025-10-01'],
            ['id' => 2, 'name' => '佐藤 花子', 'email' => 'sato@example.com', 'submitted_at' => '2025-10-02'],
            ['id' => 3, 'name' => 'John Doe', 'email' => 'john@example.com', 'submitted_at' => '2025-10-03'],
        ];

        return Inertia::render('Exam/Applications/Index', compact('applications'));
    }

    public function show($id)
    {
        // モックデータ
        $application = [
            'id' => $id,
            'name' => '山田 太郎',
            'email' => 'yamada@example.com',
            'birthdate' => '1980-01-01',
            'gender' => '男性',
            'workplace' => '〇〇病院',
            'department' => '腎臓リハビリテーション科',
            'files' => [
                ['label' => 'Certificate', 'name' => 'certificate.pdf'],
                ['label' => 'Recommendation 1', 'name' => 'recommendation_1.pdf'],
                ['label' => 'Recommendation 2', 'name' => 'recommendation_2.pdf'],
            ],
        ];

        // 自験例 1-10
        for ($i = 1; $i <= 10; $i++) {
            $application['files'][] = ['label' => "Case Report $i", 'name' => "self_report_$i.pdf"];
        }

        return Inertia::render('Exam/Applications/Show', compact('application'));
    }
}
