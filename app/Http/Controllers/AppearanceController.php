<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Per-account appearance: accent theme and light/dark scheme.
 */
class AppearanceController extends Controller
{
    public function edit(): View
    {
        return view('settings.appearance', [
            'user' => auth()->user(),
            'themes' => $this->themeCatalogue(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'theme' => ['required', Rule::in(User::THEMES)],
            'color_scheme' => ['required', Rule::in(User::COLOR_SCHEMES)],
        ], [
            'theme.required' => 'กรุณาเลือกธีมสี',
            'theme.in' => 'ธีมที่เลือกไม่ถูกต้อง',
            'color_scheme.required' => 'กรุณาเลือกโหมดการแสดงผล',
            'color_scheme.in' => 'โหมดการแสดงผลที่เลือกไม่ถูกต้อง',
        ]);

        auth()->user()->forceFill($data)->save();

        return redirect()->route('settings.appearance.edit')->with('success', 'บันทึกการแสดงผลเรียบร้อยแล้ว');
    }

    /**
     * Swatches for the picker. The two stops mirror --g-1/--g-2 in the
     * stylesheet so the preview matches what the theme actually paints.
     *
     * @return array<string, array{name: string, note: string, ink: string, from: string, to: string}>
     */
    private function themeCatalogue(): array
    {
        return [
            'teal' => ['name' => 'Teal', 'note' => 'ค่าเริ่มต้น สดใส เป็นมิตร', 'ink' => '#0d2b33', 'from' => '#2ec5f0', 'to' => '#29c39b'],
            'indigo' => ['name' => 'Indigo', 'note' => 'ทันสมัย เหมาะกับงานกลางคืน', 'ink' => '#1b1b3a', 'from' => '#818cf8', 'to' => '#c084fc'],
            'navy' => ['name' => 'Navy', 'note' => 'ทางการ สุขุม แบบองค์กร', 'ink' => '#16243d', 'from' => '#38bdf8', 'to' => '#6366f1'],
            'emerald' => ['name' => 'Emerald', 'note' => 'สุขุม อ่านสบายตา', 'ink' => '#10291f', 'from' => '#34d399', 'to' => '#a3e635'],
            'plum' => ['name' => 'Plum', 'note' => 'โดดเด่น มีบุคลิก', 'ink' => '#2b1030', 'from' => '#f0abfc', 'to' => '#f472b6'],
        ];
    }
}
