<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Department>
 */
class DepartmentFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->randomElement([
            'ทรัพยากรบุคคล', 'การเงินและบัญชี', 'เทคโนโลยีสารสนเทศ', 'การตลาด',
            'ฝ่ายขาย', 'ปฏิบัติการ', 'จัดซื้อ', 'กฎหมาย', 'บริการลูกค้า', 'วิจัยและพัฒนา',
        ]);

        return [
            'name' => $name,
            'code' => Str::upper(fake()->unique()->bothify('DPT-##??')),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }
}
