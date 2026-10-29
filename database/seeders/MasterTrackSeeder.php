<?php

namespace Database\Seeders;

use App\Models\Program;
use App\Models\Track;
use Illuminate\Database\Seeder;

class MasterTrackSeeder extends Seeder
{
    public function run(): void
    {
        $program = Program::updateOrCreate(
            [
                'name' => 'Computer Engineering',
                'degree_level' => 'MSc',
            ],
            [
                'university' => 'Politecnico di Torino',
                'is_active' => true,
            ]
        );

        $tracks = [
            [
                'name' => 'Artificial Intelligence and Data Analytics',
                'code' => 'AIDA',
                'description' => 'You will learn about the technological and theoretical aspects of big data analysis, machine learning algorithms, deep learning and artificial intelligence for data analysis. In particular, the focus is on theoretical and mathematical aspects of data analysis, technologies for big data processing, distributed processing systems and algorithms and techniques of artificial intelligence and deep learning.',
            ],
            [
                'name' => 'Automation and Intelligent Cyber-Physical Systems',
                'code' => 'AICPS',
                'description' => 'You will examine topics related to design, as well as the theoretical and experimental analysis of models by predicting, monitoring and diagnosing internal mechanisms. You will also learn about logistics and mobility management of vehicles, people and objects, with a focus on robotics, automation and modern cyber-physical system integrations.',
            ],
            [
                'name' => 'Computer Graphics and Multimedia',
                'code' => 'CGM',
                'description' => 'You will explore computer graphics, virtual and augmented reality, image processing, interactive 3D applications, and computer vision technologies for multimedia and entertainment systems.',
            ],
            [
                'name' => 'Computing and Network Infrastructures',
                'code' => 'CNI',
                'description' => 'You will specialize in large-scale computer networks, cloud and edge computing, distributed systems, network security, and modern scalable datacenter infrastructures.',
            ],
            [
                'name' => 'Embedded & Smart System Design',
                'code' => 'ESSD',
                'description' => 'You will focus on hardware-software co-design, embedded architectures, Internet of Things (IoT) devices, real-time operating systems, low-power microcontrollers and smart sensors.',
            ],
            [
                'name' => 'Human-Computer Interaction',
                'code' => 'HCI',
                'description' => 'You will study user experience (UX) design, cognitive ergonomics, multimodal interfaces, usability engineering, and human-centric software and product development.',
            ],
            [
                'name' => 'Software',
                'code' => 'SE',
                'description' => 'You will learn advanced software engineering methodologies, design patterns, microservices architecture, automated testing, DevOps and cloud-native software development.',
            ],
        ];

        foreach ($tracks as $trackData) {
            Track::updateOrCreate(
                [
                    'program_id' => $program->id,
                    'name' => $trackData['name'],
                ],
                [
                    'code' => $trackData['code'],
                    'description' => $trackData['description'],
                    'is_active' => true,
                ]
            );
        }
    }
}
