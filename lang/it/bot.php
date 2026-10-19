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
        'choose_type' => "📤 <b>Invia Materiale</b> — 📚 <b>:course</b>\n\nScegli il tipo di materiale didattico che desideri inviare:",
        'course_prompt' => "📤 <b>Invia Materiale per :course</b>\n\n"
            ."Grazie per il tuo contributo a PARSI PoliTo!\n\n"
            ."📝 Invia prima un titolo per questo materiale, poi potrai caricare il/i file.\n\n"
            .'Gli amministratori del Comitato Didattico verificheranno e pubblicheranno il file! 🎓',
        'file_received' => "📥 File ricevuto: <b>:filename</b>\n📁 File totali: <b>:count</b>\n\n💡 Puoi inviare altri file per questo materiale, oppure tocca <b>Conferma invio</b> per terminare.",
        'finish_button' => '✅ Conferma invio (:count)',
        'success' => "✅ <b>Grazie!</b>\n\n"
            ."Il tuo materiale per <b>:course</b> (:count file) è stato ricevuto ed è in fase di revisione.\n"
            .'Una volta approvato dal comitato, sarà visibile a tutti gli studenti! 🎓',
        'no_course_selected' => "⚠️ <b>Nessun insegnamento selezionato!</b>\n\n"
            ."Premi <b>📤 Invia Materiale</b> e seleziona un insegnamento prima di inviare i file.",
        'cancelled' => "❌ <b>Invio annullato.</b>",
        'cancel' => '❌ Annulla',
        'back_to_edu' => '⬅️ Torna ai Materiali Didattici',
        'approved_notification' => "🎉 <b>Buone notizie!</b>\n\nIl tuo materiale per <b>:course</b> è stato approvato dal Comitato Didattico ed è ora disponibile sul bot!\n\nGrazie per il tuo contributo alla comunità studentesca PARSI! 🎓",
        'rejected_notification' => "ℹ️ <b>Aggiornamento sulla tua proposta</b>\n\nIl tuo materiale per <b>:course</b> è stato esaminato dal Comitato Didattico e al momento non è stato possibile approvarlo per la pubblicazione.\n\nGrazie per la tua disponibilità e collaborazione! 🤝",
        'writing_title' => "Questo è il tuo titolo: <b>:title</b>\n\nSe desideri continuare, tocca <b>Sì</b> e invia il/i file desiderato/i.",
        'choose_title' => "⚠️ <b>Per favore, inserisci prima un titolo!</b>\n\nPrima di inviare i file, scrivi in questa chat un titolo descrittivo (ad es. <i>Esami 2024 con soluzioni</i>) e confermalo.",
        'title_approved' => "✅ <b>Titolo confermato!</b>\n\nOra puoi inviare il/i tuo/i file direttamente in questa chat (file singolo o album).",
        'title_rejected' => "✏️ <b>Nessun problema!</b>\n\nScrivi in questa chat il nuovo titolo che desideri utilizzare:",
        'waiting_for_files' => "📎 <b>In attesa dei file...</b>\n\nTitolo: <b>:title</b>\n\nInvia il/i tuo/i file (PDF, ZIP, DOCX o foto) in questa chat.",
    ],
    'courses' => [
        'choose_course' => "🎓 <b>:program</b>\n\nScegli un insegnamento per visualizzare i materiali:",
        'choose_program' => "📚 <b>Materiali Didattici</b>\n\nScegli il tuo corso di laurea:",
        'choose_year' => "🎓 <b>:program</b>\n\nScegli l'anno di corso per visualizzare gli insegnamenti:",
        'year_1' => '1️⃣ 1° Anno',
        'year_2' => '2️⃣ 2° Anno',
        'year_3' => '3️⃣ 3° Anno',
        'year_4' => '4️⃣ 4° Anno',
        'year_5' => '5️⃣ 5° Anno',
        'year_all' => '📚 Tutti i Corsi',
        'change_year' => '📅 Cambia Anno',
        'prev_page' => '◀️ Prec',
        'next_page' => 'Succ ▶️',
        'page_indicator' => 'Pagina :current/:total',
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
            ."📄 :title\n\n"
            .'✅ <b>Verificato da PARSI</b>',
        'caption_contributor' => "\n⭐ Contributo di: :contributor",
        'caption_drive_link' => "\n\n🔗 <b>Google Drive:</b>\n:link",
        'caption_social_media' => "\n\n✨ In bocca al lupo per gli esami! 🎓\n\n"
            ."📢 @:channel\n"
            ."🤖 @:bot",
        'send_error' => 'Impossibile inviare il file. Riprova più tardi.',
        'partial_files_error' => 'Alcuni file non sono ancora disponibili su Telegram.',
        'download_drive' => '📥 Scarica da Google Drive',
        'download_file_drive' => '📥 Scarica :filename',
        'available_on_drive' => "📥 <b>Download da Google Drive</b>\n\nQuesto materiale è disponibile su Google Drive. Clicca sul pulsante qui sotto per visualizzarlo o scaricarlo nel browser:",
    ],
    'simple_buttons' => [
        'yes' => 'Sì',
        'no' => 'No',
    ],
    'search' => [
        'prompt' => "🔍 <b>Cerca Materiali e Insegnamenti</b>\n\nDigita il nome del corso, codice o parola chiave (es. <i>Analisi</i>, <i>Fisica</i>, <i>Reti</i>):",
        'too_short' => '⚠️ Inserisci almeno 2 caratteri per la ricerca.',
        'no_results' => "🔍 Nessun corso o materiale trovato per '<b>:query</b>'.\n\nProva con un'altra parola chiave o sfoglia i corsi per corso di laurea.",
        'results_header' => "🔍 <b>Risultati per ':query'</b>\n\nSeleziona un corso o materiale qui sotto:",
        'search_again' => '🔍 Cerca Ancora',
        'cancel' => '❌ Annulla',
        'browse_all' => '📚 Sfoglia Corsi di Laurea',
    ],
    'reports' => [
        'button' => '⚠️ Segnala Materiale',
        'prompt_title' => "⚠️ <b>Segnala Materiale</b>\n\nPerché stai segnalando <b>:title</b>?\nSeleziona un motivo per aiutare i moderatori:",
        'reasons' => [
            'corrupted' => '💥 Danneggiato / Non si apre',
            'wrong_course' => '🔀 Corso / Indirizzo Errato',
            'poor_quality' => '📉 Qualità Bassa / Illeggibile',
            'copyright' => '⚖️ Violazione Copyright',
            'other' => '❓ Altro Problema',
        ],
        'success' => "✅ <b>Grazie!</b> La tua segnalazione è stata inviata.\nIl nostro team la verificherà a breve.",
        'already_reported' => "ℹ️ Hai già segnalato questo materiale di recente. Lo stiamo verificando.",
        'cancel' => '❌ Annulla',
        'cancelled' => 'Segnalazione annullata.',
    ],
    'contact' => [
        'hub_text' => "⚠️ <b>Contatti e Segnalazioni</b>\n\n"
            ."Hai bisogno di aiuto, vuoi inviarci un suggerimento o hai trovato un errore nei materiali didattici? Siamo a tua disposizione:\n\n"
            ."• <b>Segnala un Materiale:</b> Se un file è danneggiato, errato o illeggibile, clicca su <b>🔍 Cerca Materiale da Segnalare</b> oppure usa il pulsante <b>[⚠️ Segnala Materiale]</b> sotto il file scaricato.\n\n"
            ."• <b>Scrivi ai Moderatori:</b> Invia la tua richiesta o feedback direttamente al team di PARSI.",
        'report_material_btn' => '🔍 Cerca Materiale da Segnalare',
        'send_message_btn' => '💬 Invia Messaggio ai Moderatori',
        'channel_btn' => '📢 Canale Telegram PARSI',
        'write_prompt' => "✍️ <b>Contatta il Team PARSI</b>\n\nScrivi qui sotto il tuo messaggio o richiesta.\nI nostri moderatori lo riceveranno e ti risponderanno al più presto:",
        'sent_success' => "✅ <b>Messaggio Inviato!</b>\n\nGrazie per averci contattato. Il team PARSI ha ricevuto il tuo messaggio.",
        'cancelled' => 'Richiesta annullata.',
    ],
];
