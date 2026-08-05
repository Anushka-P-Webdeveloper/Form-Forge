<?php

namespace Database\Seeders;

use App\Models\Form;
use Illuminate\Database\Seeder;

class FormSeeder extends Seeder
{
    public function run()
    {
        Form::create([
            'title' => 'Internship Application',
            'slug' => 'internship-application-demo',
            'status' => 'published',
            'ai_generated' => false,
            'schema' => [
                'title' => 'Internship Application',
                'fields' => [
                    ['key' => 'personal_details', 'type' => 'heading', 'label' => 'Personal Details', 'section' => 'Personal Details', 'required' => false],
                    ['key' => 'full_name', 'type' => 'text', 'label' => 'Full Name', 'placeholder' => 'Jane Doe', 'required' => true, 'section' => 'Personal Details', 'validation' => ['min_length' => 2]],
                    ['key' => 'email', 'type' => 'email', 'label' => 'Email', 'required' => true, 'section' => 'Personal Details'],
                    ['key' => 'phone', 'type' => 'phone', 'label' => 'Phone Number', 'required' => true, 'section' => 'Personal Details'],
                    ['key' => 'education', 'type' => 'heading', 'label' => 'Education & Skills', 'section' => 'Education & Skills'],
                    ['key' => 'degree', 'type' => 'dropdown', 'label' => 'Degree', 'options' => ['B.Tech', 'B.Sc', 'BCA', 'M.Tech', 'Other'], 'required' => true, 'section' => 'Education & Skills'],
                    ['key' => 'skills', 'type' => 'checkbox', 'label' => 'Skills', 'options' => ['PHP', 'Laravel', 'JavaScript', 'React', 'MySQL'], 'required' => false, 'section' => 'Education & Skills'],
                    ['key' => 'resume', 'type' => 'file', 'label' => 'Resume', 'required' => true, 'section' => 'Education & Skills', 'validation' => ['file_types' => ['pdf', 'docx'], 'max_file_size_kb' => 5120]],
                ],
            ],
        ]);
    }
}
