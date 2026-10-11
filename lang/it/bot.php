<?php

return [
    'onboarding' => [
        'language_prompt' => "🌐 <b>Welcome to PARSI PoliTo Bot!</b>\n\n"
            ."Please select your preferred language:\n"
            ."Seleziona la tua lingua preferita:\n"
            .'لطفاً زبان مورد نظر خود را انتخاب کنید:',
        'program_prompt' => "🎓 <b>Seleziona il tuo Corso di Laurea</b>\n\n"
            .'Scegli il tuo percorso di studi al Politecnico di Torino per personalizzare i materiali didattici:',
        'completed' => "🎉 <b>Configurazione completata, :name!</b>\n\n"
            .'Corso impostato su: <b>:program</b>.',
        'ready' => 'Cosa vorresti fare?',
    ],
    'menu' => [
        'welcome' => "👋 <b>Benvenuto su PARSI Education Bot, :name!</b>\n\n"
            ."L'Associazione Studentesca PARSI è qui per supportarti con materiali didattici, servizi per gli studenti e guide universitarie.\n\n"
            .'Quale servizio vorresti utilizzare oggi?',
        'choose_option' => "Scegli un'opzione dal menu sottostante, o invia /start.",
        'closed' => "<b>:lastSection</b>\n\nMenu chiuso.",
        'default_section' => 'Menu',
    ],
    'settings' => [
        'title' => "⚙️ <b>Impostazioni</b>\n\n"
            ."Lingua attuale: <b>:lang</b>\n"
            ."Corso di Laurea: <b>:program</b>\n\n"
            .'Cosa desideri modificare?',
        'not_selected' => 'Non selezionato',
        'change_language' => '🌐 Cambia Lingua',
        'change_program' => '🎓 Cambia Corso di Laurea',
        'back_main' => '⬅️ Torna al Menu Principale',
        'change_language_prompt' => "🌐 <b>Select Preferred Language</b>\n\n"
            ."Please select your preferred language:\n"
            ."Seleziona la tua lingua preferita:\n"
            .'لطفاً زبان مورد نظر خود را انتخاب کنید:',
        'change_program_prompt' => "🔄 <b>Seleziona Corso di Laurea</b>\n\nScegli un corso di laurea:",
    ],
    'sections' => [
        'educational_files' => '📚 Materiali Didattici',
        'settings' => '⚙️ Impostazioni',
        'edisu' => '🔍 Edisu',
        'deadlines' => '❓ Scadenze Importanti',
        'contact' => '⚠️ Contatti / Segnalazioni',
        'edisu_coming_soon' => "🔍 <b>Edisu</b>\n\nQuesta sezione sarà disponibile a breve!",
        'deadlines_coming_soon' => "❓ <b>Scadenze Importanti</b>\n\nQuesta sezione sarà disponibile a breve!",
        'contact_coming_soon' => "⚠️ <b>Contatti / Segnalazioni</b>\n\nQuesta sezione sarà disponibile a breve!",
    ],
    'submit_material' => [
        'prompt' => "📤 <b>Contribuisci con Materiale Didattico</b>\n\n"
            ."Aiuta gli studenti del PoliTo condividendo temi d'esame, appunti o riassunti!\n\n"
            ."Per inviare:\n"
            ."1. Premi <b>📚 Materiali Didattici</b>\n"
            ."2. Scegli Corso di Laurea e Insegnamento\n"
            ."3. Premi <b>📤 Invia Materiale per questo Corso</b>\n\n"
            .'Gli amministratori del Comitato Didattico verificheranno e pubblicheranno il materiale! 🎓',
        'choose_course' => "📤 <b>Invia Materiale</b> — 🎓 <b>:program</b>\n\nScegli l'insegnamento per cui desideri caricare il materiale:",
        'course_prompt' => "📤 <b>Invia Materiale per :course</b>\n\n"
            ."Grazie per il tuo contributo a PARSI PoliTo!\n\n"
            ."📄 Invia il tuo documento (PDF, ZIP, DOCX) o foto direttamente in questa chat.\n"
            ."💡 Includi il titolo o l'anno d'esame nella didascalia del file.\n\n"
            .'Gli amministratori del Comitato Didattico verificheranno e pubblicheranno il file! 🎓',
        'success' => "✅ <b>Grazie!</b>\n\n"
            ."Il tuo materiale per <b>:course</b> è stato ricevuto ed è in fase di revisione.\n"
            .'Una volta approvato dal comitato, sarà visibile a tutti gli studenti! 🎓',
        'no_course_selected' => "⚠️ <b>Nessun insegnamento selezionato!</b>\n\n"
            ."Premi <b>📤 Invia Materiale</b> e seleziona un insegnamento prima di inviare i file.",
        'cancelled' => "❌ <b>Invio annullato.</b>",
        'cancel' => '❌ Annulla',
        'back_to_edu' => '⬅️ Torna ai Materiali Didattici',
    ],
    'courses' => [
        'choose_course' => "🎓 <b>:program</b>\n\nScegli un insegnamento per visualizzare i materiali:",
        'choose_program' => "📚 <b>Materiali Didattici</b>\n\nScegli il tuo corso di laurea:",
        'choose_type' => "🎓 <b>:course</b>\n\nScegli una tipologia per visualizzare i materiali:",
        'saved_as_default' => "✅ <i>Salvato come corso predefinito.</i>\n\n",
        'program_not_found' => 'Corso di laurea non trovato. Scegline un altro:',
        'available_resources' => "<b>Materiali disponibili</b>\n\nScegli un materiale da scaricare:",
        'no_courses' => 'ℹ️ Nessun insegnamento ancora registrato',
        'no_types' => 'ℹ️ Nessun materiale disponibile per questa voce',
        'no_materials' => 'ℹ️ Nessun file ancora caricato per questo insegnamento',
        'submit_for_course' => '📤 Invia Materiale per questo Corso',
        'change_program' => '🔄 Cambia Corso di Laurea',
        'back_courses' => '⬅️ Torna agli Insegnamenti',
        'back_types' => '⬅️ Torna alle Tipologie',
        'back_main' => '⬅️ Torna al Menu Principale',
    ],
    'resources' => [
        'not_available' => 'Questo materiale non è più disponibile.',
        'no_files' => 'Nessun file scaricabile al momento disponibile per questa risorsa.',
        'caption_header' => "📚 <b>:course</b>\n"
            ."📄 <i>:title</i>\n"
            ."🎓 Politecnico di Torino\n\n"
            .'✅ <b>Verificato da PARSI</b>',
        'caption_contributor' => "\n⭐ Contributo di: :contributor",
        'send_error' => 'Impossibile inviare il file. Riprova più tardi.',
        'partial_files_error' => 'Alcuni file non sono ancora disponibili su Telegram.',
        'download_drive' => '📥 Scarica da Google Drive',
        'download_file_drive' => '📥 Scarica :filename',
        'available_on_drive' => "📥 <b>Download da Google Drive</b>\n\nQuesto materiale è disponibile su Google Drive. Clicca sul pulsante qui sotto per visualizzarlo o scaricarlo nel browser:",
    ],
];
