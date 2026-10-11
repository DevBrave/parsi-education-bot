<?php

return [
    'onboarding' => [
        'language_prompt' => "🌐 <b>Welcome to PARSI PoliTo Bot!</b>\n\n"
            ."Please select your preferred language:\n"
            ."Seleziona la tua lingua preferita:\n"
            .'لطفاً زبان مورد نظر خود را انتخاب کنید:',
        'program_prompt' => "🎓 <b>Select your Degree Programme</b>\n\n"
            .'Please choose your study track at Politecnico di Torino to customize your course catalog:',
        'completed' => "🎉 <b>Setup complete, :name!</b>\n\n"
            .'Your programme is set to <b>:program</b>.',
        'ready' => 'What would you like to do?',
    ],
    'menu' => [
        'welcome' => "👋 <b>Welcome to PARSI Education Bot, :name!</b>\n\n"
            ."PARSI Student Association is here to support you with educational materials, student services, and university guides.\n\n"
            .'What service would you like to use today?',
        'choose_option' => "Please choose an option from the menu below, or send /start.",
        'closed' => "<b>:lastSection</b>\n\nMenu closed.",
        'default_section' => 'Menu',
    ],
    'settings' => [
        'title' => "⚙️ <b>Settings</b>\n\n"
            ."Current Language: <b>:lang</b>\n"
            ."Current Degree: <b>:program</b>\n\n"
            .'What would you like to update?',
        'not_selected' => 'Not selected',
        'change_language' => '🌐 Change Language',
        'change_program' => '🎓 Change Degree Programme',
        'back_main' => '⬅️ Back to Main Menu',
        'change_language_prompt' => "🌐 <b>Select Preferred Language</b>\n\n"
            ."Please select your preferred language:\n"
            ."Seleziona la tua lingua preferita:\n"
            .'لطفاً زبان مورد نظر خود را انتخاب کنید:',
        'change_program_prompt' => "🔄 <b>Select Degree Programme</b>\n\nChoose a programme to switch to:",
    ],
    'sections' => [
        'educational_files' => '📚 Educational Files',
        'settings' => '⚙️ Settings',
        'edisu' => '🔍 Edisu',
        'deadlines' => '❓ Important Deadlines',
        'contact' => '⚠️ Contact / Report',
        'edisu_coming_soon' => "🔍 <b>Edisu</b>\n\nThis section is coming soon!",
        'deadlines_coming_soon' => "❓ <b>Important Deadlines</b>\n\nThis section is coming soon!",
        'contact_coming_soon' => "⚠️ <b>Contact / Report</b>\n\nThis section is coming soon!",
    ],
    'submit_material' => [
        'prompt' => "📤 <b>Contribute Study Material</b>\n\n"
            ."Help fellow PoliTo students by submitting past exams, lecture notes, summaries, or exercises!\n\n"
            ."To submit:\n"
            ."1. Tap <b>📚 Educational Files</b>\n"
            ."2. Choose your Degree & Course\n"
            ."3. Tap <b>📤 Submit Material for this Course</b>\n\n"
            .'The Education Committee admins will review, verify, and publish your material! 🎓',
        'choose_course' => "📤 <b>Submit Material</b> — 🎓 <b>:program</b>\n\nSelect the course you want to submit material for:",
        'course_prompt' => "📤 <b>Submit Material for :course</b>\n\n"
            ."Thank you for contributing to PARSI PoliTo!\n\n"
            ."📄 Send your document (PDF, ZIP, DOCX) or photo directly into this chat.\n"
            ."💡 Include the title or notes in the file caption.\n\n"
            .'The Education Committee admins will review and publish your submission! 🎓',
        'success' => "✅ <b>Thank you!</b>\n\n"
            ."Your material for <b>:course</b> has been received and submitted for review.\n"
            .'Once verified by our committee, it will be published for all students! 🎓',
        'no_course_selected' => "⚠️ <b>No course selected!</b>\n\n"
            ."Please tap <b>📤 Submit Material</b> and select a course before sending files.",
        'cancelled' => "❌ <b>Submission cancelled.</b>",
        'cancel' => '❌ Cancel',
        'back_to_edu' => '⬅️ Back to Educational Files',
    ],
    'courses' => [
        'choose_course' => "🎓 <b>:program</b>\n\nChoose a course to view materials:",
        'choose_program' => "📚 <b>Educational Files</b>\n\nChoose your degree programme:",
        'choose_type' => "🎓 <b>:course</b>\n\nChoose a type to view materials:",
        'saved_as_default' => "✅ <i>Saved as your default programme.</i>\n\n",
        'program_not_found' => 'Programme not found. Please choose another one:',
        'available_resources' => "<b>Available resources</b>\n\nChoose a resource to download:",
        'no_courses' => 'ℹ️ No courses registered yet',
        'no_types' => 'ℹ️ There is no resource for this',
        'no_materials' => 'ℹ️ No materials uploaded for this yet',
        'submit_for_course' => '📤 Submit a Material for this Course',
        'change_program' => '🔄 Change Programme',
        'back_courses' => '⬅️ Back to Courses',
        'back_types' => '⬅️ Back to Types',
        'back_main' => '⬅️ Back to Main Menu',
    ],
    'resources' => [
        'not_available' => 'This resource is no longer available.',
        'no_files' => 'No downloadable files are available for this resource yet.',
        'caption_header' => "📚 <b>:course</b>\n"
            ."📄 <i>:title</i>\n"
            ."🎓 Politecnico di Torino\n\n"
            .'✅ <b>Verified by PARSI</b>',
        'caption_contributor' => "\n⭐ Contributed by: :contributor",
        'send_error' => 'A file could not be sent. Please try again later.',
        'partial_files_error' => 'Some files are not available through Telegram yet.',
        'download_drive' => '📥 Download from Google Drive',
        'download_file_drive' => '📥 Download :filename',
        'available_on_drive' => "📥 <b>Google Drive Download</b>\n\nThis material is available directly via Google Drive. Tap the button below to view or download it directly in your browser:",
    ],
];
