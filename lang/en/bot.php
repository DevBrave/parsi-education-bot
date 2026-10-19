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
        'educational_files' => '📚 Study Resources',
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
            ."1. Tap <b>📚 Study Resources</b>\n"
            ."2. Choose your Degree & Course\n"
            ."3. Tap <b>📤 Submit Material for this Course</b>\n\n"
            .'The Education Committee admins will review, verify, and publish your material! 🎓',
        'choose_course' => "📤 <b>Submit Material</b> — 🎓 <b>:program</b>\n\nSelect the course you want to submit material for:",
        'choose_type' => "📤 <b>Submit Material</b> — 📚 <b>:course</b>\n\nChoose the type of material you want to submit:",
        'course_prompt' => "📤 <b>Submit Material for :course</b>\n\n"
            ."Thank you for contributing to PARSI PoliTo!\n\n"
            ."📝 Please send a title for this material first, then you can upload your file(s).\n\n"
            .'The Education Committee admins will review and publish your submission! 🎓',
        'file_received' => "📥 Received: <b>:filename</b>\n📁 Total files: <b>:count</b>\n\n💡 You can send more files for this material, or tap <b>Done Uploading</b> below when finished.",
        'finish_button' => '✅ Done Uploading (:count)',
        'success' => "✅ <b>Thank you!</b>\n\n"
            ."Your material for <b>:course</b> (:count file(s)) has been received and submitted for review.\n"
            .'Once verified by our committee, it will be published for all students! 🎓',
        'no_course_selected' => "⚠️ <b>No course selected!</b>\n\n"
            ."Please tap <b>📤 Submit Material</b> and select a course before sending files.",
        'cancelled' => "❌ <b>Submission cancelled.</b>",
        'cancel' => '❌ Cancel',
        'back_to_edu' => '⬅️ Back to Study Resources',
        'approved_notification' => "🎉 <b>Good news!</b>\n\nYour submitted material for <b>:course</b> has been approved by the Education Committee and is now published on the bot!\n\nThank you for supporting fellow PoliTo students! 🎓",
        'rejected_notification' => "ℹ️ <b>Submission Update</b>\n\nYour submitted file for <b>:course</b> was reviewed by the Education Committee and could not be approved for publication at this time.\n\nThank you for your effort and support! 🤝",
        'writing_title' => "This is your title: <b>:title</b>\n\nIf you want to continue, tap <b>Yes</b> and send your file(s).",
        'choose_title' => "⚠️ <b>Please choose a title first!</b>\n\nBefore uploading your file(s), please type a descriptive title (e.g. <i>2024 Exams with solutions</i>) into this chat and confirm it.",
        'title_approved' => "✅ <b>Title confirmed!</b>\n\nYou can now send your file(s) directly into this chat (single file or album).",
        'title_rejected' => "✏️ <b>No problem!</b>\n\nPlease type the new title you would like to use:",
        'waiting_for_files' => "📎 <b>Waiting for files...</b>\n\nTitle: <b>:title</b>\n\nPlease send your file(s) (PDF, ZIP, DOCX, or photo) into this chat.",
    ],
    'courses' => [
        'choose_course' => "🎓 <b>:program</b>\n\nChoose a course to view materials:",
        'choose_program' => "📚 <b>Study Resources</b>\n\nChoose your degree programme:",
        'choose_year' => "🎓 <b>:program</b>\n\nChoose your academic year to view courses:",
        'year_1' => '1️⃣ 1st Year',
        'year_2' => '2️⃣ 2nd Year',
        'year_3' => '3️⃣ 3rd Year',
        'year_4' => '4️⃣ 4th Year',
        'year_5' => '5️⃣ 5th Year',
        'year_all' => '📚 All Courses',
        'change_year' => '📅 Change Year',
        'prev_page' => '◀️ Prev',
        'next_page' => 'Next ▶️',
        'page_indicator' => 'Page :current/:total',
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
            ."📄 :title\n\n"
            .'✅ <b>Verified by PARSI</b>',
        'caption_contributor' => "\n⭐ Contributed by: :contributor",
        'caption_drive_link' => "\n\n🔗 <b>Google Drive:</b>\n:link",
        'caption_social_media' => "\n\n✨ Good luck with your exams! 🎓\n\n"
            ."📢 @:channel\n"
            ."🤖 @:bot",
        'send_error' => 'A file could not be sent. Please try again later.',
        'partial_files_error' => 'Some files are not available through Telegram yet.',
        'download_drive' => '📥 Download from Google Drive',
        'download_file_drive' => '📥 Download :filename',
        'available_on_drive' => "📥 <b>Google Drive Download</b>\n\nThis material is available directly via Google Drive. Tap the button below to view or download it directly in your browser:",
    ],
    'simple_buttons' => [
        'yes' => 'Yes',
        'no' => 'No'
    ],
    'search' => [
        'prompt' => "🔍 <b>Search Materials & Courses</b>\n\nPlease type the course name, course code, or keyword you are looking for (e.g. <i>Calculus</i>, <i>Physics</i>, <i>Networks</i>):",
        'too_short' => '⚠️ Please type at least 2 characters to search.',
        'no_results' => "🔍 No courses or materials found matching '<b>:query</b>'.\n\nTry another keyword or browse courses by programme.",
        'results_header' => "🔍 <b>Search Results for ':query'</b>\n\nSelect a course or material below:",
        'search_again' => '🔍 Search Again',
        'cancel' => '❌ Cancel',
        'browse_all' => '📚 Browse Programmes',
    ],
    'reports' => [
        'button' => '⚠️ Report Material',
        'prompt_title' => "⚠️ <b>Report Material</b>\n\nWhy are you reporting <b>:title</b>?\nSelect a reason below to help moderators review it:",
        'reasons' => [
            'corrupted' => '💥 Corrupted / Can\'t Open',
            'wrong_course' => '🔀 Wrong Course / Major',
            'poor_quality' => '📉 Poor Quality / Unreadable',
            'copyright' => '⚖️ Copyright Concern',
            'other' => '❓ Other Issue',
        ],
        'success' => "✅ <b>Thank you!</b> Your report has been submitted.\nOur team will review this material shortly.",
        'already_reported' => "ℹ️ You have already reported this material recently. Our team is currently reviewing it.",
        'cancel' => '❌ Cancel',
        'cancelled' => 'Report cancelled.',
    ],
    'contact' => [
        'hub_text' => "⚠️ <b>Contact & Material Reports</b>\n\n"
            ."Need assistance, have feedback, or found an issue with study materials? We are here to help:\n\n"
            ."• <b>Report a Material:</b> If a document is corrupted, incorrect, or unreadable, tap <b>🔍 Search Material to Report</b> or use the <b>[⚠️ Report Material]</b> button directly under any downloaded file.\n\n"
            ."• <b>Message Admins:</b> Send your question, suggestion, or feedback directly to the PARSI team.",
        'report_material_btn' => '🔍 Search Material to Report',
        'send_message_btn' => '💬 Send Message to Admins',
        'channel_btn' => '📢 PARSI Telegram Channel',
        'write_prompt' => "✍️ <b>Contact PARSI Team</b>\n\nPlease type your message, feedback, or inquiry below.\nOur administrators will review it and reply as soon as possible:",
        'sent_success' => "✅ <b>Message Sent!</b>\n\nThank you for reaching out. The PARSI team has received your message and will review it.",
        'cancelled' => 'Contact cancelled.',
    ],
];
