<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class InitialDataSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admins')->insertOrIgnore([
            'login_id'   => 'admin',
            'password'   => Hash::make('admin1234!'),
            'name'       => '시스템관리자',
            'email'      => 'admin@company.com',
            'role'       => 'super_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $templateId = DB::table('design_templates')->insertGetId([
            'name'             => '기본 사원증 템플릿',
            'background_image' => 'templates/default_bg.png',
            'canvas_width'     => 640,
            'canvas_height'    => 1010,
            'is_default'       => true,
            'is_active'        => true,
            'description'      => '기본 제공 사원증 디자인입니다.',
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $fields = [
            ['field_key'=>'photo','label'=>'증명사진','field_type'=>'image','pos_x'=>220,'pos_y'=>180,'width'=>200,'height'=>260,'font_size'=>0,'sort_order'=>1],
            ['field_key'=>'name','label'=>'이름','field_type'=>'text','pos_x'=>320,'pos_y'=>480,'width'=>null,'height'=>null,'font_size'=>28,'sort_order'=>2],
            ['field_key'=>'name_en','label'=>'영문이름','field_type'=>'text','pos_x'=>320,'pos_y'=>520,'width'=>null,'height'=>null,'font_size'=>16,'sort_order'=>3],
            ['field_key'=>'department','label'=>'부서','field_type'=>'text','pos_x'=>320,'pos_y'=>560,'width'=>null,'height'=>null,'font_size'=>18,'sort_order'=>4],
            ['field_key'=>'position','label'=>'직책','field_type'=>'text','pos_x'=>320,'pos_y'=>595,'width'=>null,'height'=>null,'font_size'=>18,'sort_order'=>5],
            ['field_key'=>'employee_number','label'=>'사번','field_type'=>'text','pos_x'=>320,'pos_y'=>630,'width'=>null,'height'=>null,'font_size'=>14,'sort_order'=>6],
            ['field_key'=>'qr_code','label'=>'QR코드','field_type'=>'qr_code','pos_x'=>230,'pos_y'=>700,'width'=>180,'height'=>180,'font_size'=>0,'sort_order'=>7],
        ];

        foreach ($fields as $field) {
            DB::table('field_mappings')->insert(array_merge($field, [
                'design_template_id' => $templateId,
                'font_color'   => '#333333',
                'font_family'  => 'NanumGothic',
                'text_align'   => 'center',
                'is_bold'      => in_array($field['field_key'], ['name']),
                'is_visible'   => true,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]));
        }
    }
}
