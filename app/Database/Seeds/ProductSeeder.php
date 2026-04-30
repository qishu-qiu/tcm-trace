<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'tenant_id' => 1,
                'name' => '人参',
                'alias' => '黄参,地精,神草',
                'origin' => '吉林省长白山',
                'category' => '根茎类',
                'specification' => '一等品 25g/支',
                'quality_grade' => '特级',
                'image_url' => '',
                'description' => '长白山人参，产地正宗，品质优良。大补元气，复脉固脱，补脾益肺，生津，安神。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 1,
                'name' => '三七',
                'alias' => '田七,金不换',
                'origin' => '云南省文山州',
                'category' => '根茎类',
                'specification' => '20头/斤',
                'quality_grade' => '一等品',
                'image_url' => '',
                'description' => '文山三七，素有"金不换"之称。散瘀止血，消肿定痛。用于咯血，吐血，衄血，便血，崩漏，外伤出血，胸腹刺痛，跌扑肿痛。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 1,
                'name' => '黄芪',
                'alias' => '黄耆,独椹',
                'origin' => '内蒙古自治区',
                'category' => '根茎类',
                'specification' => '特级 0.8cm以上',
                'quality_grade' => '特级',
                'image_url' => '',
                'description' => '内蒙古黄芪，补气升阳，益卫固表，利水消肿，生津养血，行滞通痹，托毒排脓，敛疮生肌。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 1,
                'name' => '当归',
                'alias' => '秦归,云归,西当归',
                'origin' => '甘肃省岷县',
                'category' => '根茎类',
                'specification' => '全归片',
                'quality_grade' => '一等品',
                'image_url' => '',
                'description' => '岷县当归，补血活血，调经止痛，润肠通便。用于血虚萎黄，眩晕心悸，月经不调，经闭痛经，虚寒腹痛，风湿痹痛，跌扑损伤，痈疽疮疡，肠燥便秘。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 1,
                'name' => '甘草',
                'alias' => '国老,甜草',
                'origin' => '内蒙古自治区',
                'category' => '根茎类',
                'specification' => '精选片',
                'quality_grade' => '一等品',
                'image_url' => '',
                'description' => '甘草，补脾益气，清热解毒，祛痰止咳，缓急止痛，调和诸药。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 2,
                'name' => '牛黄清心丸',
                'alias' => '',
                'origin' => '北京市',
                'category' => '中成药',
                'specification' => '每丸3g',
                'quality_grade' => '精品',
                'image_url' => '',
                'description' => '清心化痰，镇惊祛风。用于风痰阻窍所致的头晕目眩、痰涎壅盛、神志混乱、言语不清及惊风抽搐、癫痫。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 2,
                'name' => '安宫牛黄丸',
                'alias' => '',
                'origin' => '北京市',
                'category' => '中成药',
                'specification' => '每丸3g',
                'quality_grade' => '精品',
                'image_url' => '',
                'description' => '清热解毒，镇惊开窍。用于热病，邪入心包，高热惊厥，神昏谵语；中风昏迷及脑炎、脑膜炎、中毒性脑病、脑出血、败血症见上述证候者。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 3,
                'name' => '云南白药粉',
                'alias' => '',
                'origin' => '云南省昆明市',
                'category' => '中成药',
                'specification' => '4g/瓶',
                'quality_grade' => '精品',
                'image_url' => '',
                'description' => '化瘀止血，活血止痛，解毒消肿。用于跌打损伤，瘀血肿痛，吐血，咳血，便血，痔血，崩漏下血，疮疡肿毒及软组织挫伤，闭合性骨折，支气管扩张及肺结核咳血，溃疡病出血，以及皮肤感染性疾病。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 3,
                'name' => '云南白药气雾剂',
                'alias' => '',
                'origin' => '云南省昆明市',
                'category' => '中成药',
                'specification' => '85g+60g',
                'quality_grade' => '精品',
                'image_url' => '',
                'description' => '活血散瘀，消肿止痛。用于跌打损伤，瘀血肿痛，肌肉酸痛及风湿疼痛。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ],
            [
                'tenant_id' => 1,
                'name' => '枸杞',
                'alias' => '枸杞子,血杞子',
                'origin' => '宁夏回族自治区中宁县',
                'category' => '果实种子类',
                'specification' => '特级 280粒/50g',
                'quality_grade' => '特级',
                'image_url' => '',
                'description' => '宁夏中宁枸杞，滋补肝肾，益精明目。用于虚劳精亏，腰膝酸痛，眩晕耳鸣，内热消渴，血虚萎黄，目昏不明。',
                'status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]
        ];

        foreach ($data as $item) {
            $this->db->table('products')->insert($item);
        }
    }
}
