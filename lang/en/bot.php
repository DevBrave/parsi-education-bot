<?php

return [
    'onboarding' => [
        'language_prompt' => "🌐 <b>Welcome to PARSI PoliTo Bot!</b>\n\n"
            ."Please select your preferred language:\n"
            ."Seleziona la tua lingua preferita:\n"
            .'لطفاً زبان مورد نظر خود را انتخاب کنید:',
        'program_prompt' => "🎓 <b>Select your Degree Programme</b>\n\n"
            .'Please choose your study track at Politecnico di Torino to customize your course catalog:',
        'degree_prompt' => "🎓 <b>Select Degree Level</b>\n\n"
            ."Please select your degree level :",
        'programs_by_degree_prompt' => "📚 <b>Select Programme (:degree)</b>\n\n"
            ."Please choose your study programme:",
        'back_degrees' => '⬅️ Back to Degree Levels',
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
        'contact' => '📞 Contact Us',
        'report' => '⚠️ Report',
        'tutorials' => '📑 Tutorials & Guides',
        'groups' => '👥 Student Groups',
        'edisu_coming_soon' => "🔍 <b>Edisu</b>\n\nThe Edisu guide section will be available soon! 🚀",
        'deadlines_coming_soon' => "❓ <b>Important Deadlines</b>\n\nAcademic deadlines will be available soon! 🚀",
        'tutorials_coming_soon' => "📑 <b>Tutorials & Guides</b>\n\nPARSI tutorials and educational guides will be available soon! 🚀",
        'groups_coming_soon' => "👥 <b>Student Groups</b>\n\nMajor and course-specific student groups will be available here soon! 🚀",
        'contact_coming_soon' => "⚠️ <b>Contact / Report</b>\n\nThis section will be available soon! 🚀",
    ],
    'report' => [
        'title' => "⚠️ <b>Report & Support Center</b>\n\n"
            . "Please let us know if you encountered an issue with any course file or if you would like to report something to the PARSI team:\n\n"
            . "• To report an incorrect or damaged course material, search for it and tap <b>Report</b>.\n"
            . "• To send a report or feedback to administrators, tap <b>Send Report Message</b>.",
        'report_material_btn' => '🔍 Search Material to Report',
        'send_report_btn' => '📝 Send Report to Admins',
    ],
    'tutorials' => [
        'title' => "📑 <b>PARSI Educational Guides & Tutorials</b>\n\n"
            . "Explore step-by-step guides and tutorials prepared by the PARSI student association for Politecnico di Torino students.\n\n"
            . "Select a guide below to open the corresponding file/post:",
        'edisu_btn' => '🏛️ EDISU Scholarship & Housing Guide',
        'enrollment_btn' => '🎓 Enrollment & Visa Guide',
        'didattica_btn' => '💻 Portale della Didattica Tutorial',
        'permesso_btn' => '🪪 Permesso di Soggiorno Guide',
        'channel_guides_btn' => '📢 Browse All Guides in PARSI Channel',
    ],
    'education_hub' => [
        'title' => "📚 <b>Study Resources</b>\n\n"
            ."Welcome to the PARSI academic repository for Politecnico di Torino.\n\n"
            ."Here you can explore verified course materials, search across all subjects, or contribute past exams and notes for fellow students!\n\n"
            ."What would you like to do?",
        'browse_btn' => '📚 View Courses & Materials',
        'search_btn' => '🔍 Search Materials',
        'submit_btn' => '📤 Submit Material',
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
        'rejected_notification' => "❌ <b>Submission Update</b>\n\nYour submitted file for <b>:course</b> was reviewed by the Education Committee and could not be approved for publication at this time.\n\nThank you for your effort and support! 🤝",
        'writing_title' => "This is your title: <b>:title</b>\n\nIf you want to continue, tap <b>Yes</b> and send your file(s).",
        'choose_title' => "⚠️ <b>Please choose a title first!</b>\n\nBefore uploading your file(s), please type a descriptive title (e.g. <i>2024 Exams with solutions</i>) into this chat and confirm it.",
        'title_approved' => "✅ <b>Title confirmed!</b>\n\nYou can now send your file(s) directly into this chat (single file or album).",
        'title_rejected' => "✏️ <b>No problem!</b>\n\nPlease type the new title you would like to use:",
        'waiting_for_files' => "📎 <b>Waiting for files...</b>\n\nTitle: <b>:title</b>\n\nPlease send your file(s) (PDF, ZIP, DOCX, or photo) into this chat.",
    ],
    'courses' => [
        'choose_course' => "\nChoose a course to view materials:",
        'choose_program' => "📚 <b>Study Resources</b>\n\nChoose your degree programme:",
        'choose_year' => "🎓 <b>:program</b>\n\nChoose your academic year to view courses:",
        'choose_track' => "🎯 <b>:program</b>\n\nChoose your specialization track:",
        'change_track' => '🎯 Change Track',
        'all_tracks' => '🌐 All Tracks (Show All Courses)',
        'saved_track' => "✅ <i>Track saved: :track</i>\n\n",
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
        'back_edu' => '⬅️ Back to Study Resources',
        'back_main' => '⬅️ Back to Main Menu',
    ],
    'tracks' => [
        'choose_track' => "🎯 <b>:program</b>\n\nChoose your specialization track (inner major):",
        'change_track' => '🎯 Change Track',
        'all_tracks' => '🌐 All Tracks (Show All Courses)',
        'saved_track' => "✅ <i>Track saved: :track</i>\n\n",
        'back_to_programs' => '⬅️ Back to Programs',
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
        'get_all_in_bot' => '📥 Get All Files in Bot',
        'open_course_in_bot' => '📖 View Course in Bot',
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
        'hub_text' => "📞 <b>Contact Us & Student Support</b>\n\n"
            ."Need assistance, have questions, or want to report an issue with study materials? We are here to help:\n\n"
            ."• <b>Message Admins:</b> Send your question, suggestion, or feedback via direct message to the PARSI Telegram channel.\n"
            ."• <b>Report a Material:</b> If a document is corrupted, incorrect, or unreadable, search for it to file a report.\n"
            ."• <b>Community Channel:</b> Join our official Telegram channel for updates and announcements.",
        'report_material_btn' => '🔍 Search Material to Report',
        'send_message_btn' => '💬 Send Message to Admins',
        'channel_btn' => '📢 PARSI Telegram Channel',
        'write_prompt' => "✍️ <b>Contact PARSI Team</b>\n\nPlease type your message, feedback, or inquiry below.\nOur administrators will review it and reply as soon as possible:",
        'sent_success' => "✅ <b>Message Sent!</b>\n\nThank you for reaching out. The PARSI team has received your message and will review it.",
        'cancelled' => 'Contact cancelled.',
    ],
    'about' => [
        'text' => "<b>PARSI</b> is the Iranian Students Association at <b>Politecnico di Torino</b>, created to support, connect, and assist Iranian students throughout their university experience.\n\n"
            . "🎯 <b>Our Mission</b>\n"
            . "We aim to make academic and student life easier by collecting and providing <b>course materials, lecture notes, past exam questions, educational guides</b>, as well as updates on important <b>university, EDISU, and administrative deadlines</b>.\n\n"
            . "🤝 <b>Built by Students, for Students</b>\n"
            . "A large part of the resources available through this bot is contributed by students themselves and is reviewed and organized before being shared with the community.\n\n"
            . "This bot is built through student collaboration, with the goal of making useful knowledge, resources, and experience accessible to everyone.\n\n"
            . "<b>Welcome to the Iranian student community at Politecnico di Torino.</b>",
        'channel_btn' => '📢 Official Telegram Channel',
        'insta_btn' => '📸 Official Instagram Page',
        'contact_btn' => '💬 Contact PARSI Team',
        'members_btn' => '👥 PARSI Team Members',
        'back_about' => '⬅️ Back to About Us',
        'members_text' => "👥 <b>PARSI Board & Committee Members</b>\n\n"
            . "• <b>Kave Alikhani</b> — President\n"
            . "• <b>Arya Gholami</b> — Vice President\n"
            . "• <b>Anita Javidian</b> — Head of Media Committee\n"
            . "• <b>Arya Mansouri</b> — Financial Manager\n"
            . "• <b>Nika Taherian</b> — Head of Cultural Committee\n"
            . "• <b>Ali Arsalan</b> — Head of Executive Committee\n"
            . "• <b>Hamidreza Ramezani</b> — Head of Education Committee\n"
    ],
    'rate_limit' => [
        'too_many_messages' => "⚠️ <b>Too Many Requests</b>\n\nYou are sending messages too quickly. Please wait <b>:seconds seconds</b> before trying again.",
        'too_many_uploads' => "⚠️ <b>Hourly Upload Limit Reached</b>\n\nTo prevent abuse, student accounts can upload up to 20 files per hour. Please wait <b>:minutes minutes</b> before uploading more materials.",
        'too_many_clicks' => '⚠️ Please slow down! You are clicking buttons too rapidly.',
    ],
    'file_security' => [
        'rejected' => "🛡️ <b>Security Alert: File Rejected</b>\n\nThe file <code>:filename</code> could not be accepted for the following reason:\n<b>:reason</b>\n\n📌 <i>Please only upload genuine educational materials such as PDF, Word, PowerPoint, or ZIP files.</i>",
        'reasons' => [
            'dangerous_extension' => 'Executable or script files are strictly prohibited for system and student safety.',
            'unsupported_extension' => 'This file format is not supported. Please upload a PDF, Word, PowerPoint, or ZIP file.',
            'double_extension' => 'Files with disguised or double extensions are blocked for security.',
            'missing_extension' => 'File is missing a valid extension.',
            'magic_bytes_mismatch' => 'File binary signature does not match its extension (possible extension spoofing).',
            'malicious_signature_detected' => 'Malicious executable signature detected inside file header.',
            'embedded_script_detected' => 'Embedded script or code payload detected in file.',
            'virus_detected' => 'Antivirus scan flagged this file as unsafe.',
            'empty_file' => 'The uploaded file is empty (0 bytes).',
            'default' => 'File failed security verification.',
        ],
    ],
];

