<?php

return [
    'onboarding' => [
        'language_prompt' => "🌐 <b>Welcome to PARSI PoliTo Bot!</b>\n\n"
            . "Please select your preferred language:\n"
            . "Seleziona la tua lingua preferita:\n"
            . 'لطفاً زبان مورد نظر خود را انتخاب کنید:',
        'program_prompt' => "🎓 <b>Seleziona il tuo Corso di Laurea</b>\n\n"
            . 'Scegli il tuo percorso di studi al Politecnico di Torino per personalizzare i materiali didattici:',
        'degree_prompt' => "🎓 <b>Seleziona Livello di Laurea</b>\n\n"
            . 'Scegli il tuo livello di studio:',
        'programs_by_degree_prompt' => "📚 <b>Seleziona Corso di Laurea (:degree)</b>\n\n"
            . 'Scegli il tuo corso di laurea:',
        'back_degrees' => '⬅️ Torna ai Livelli di Laurea',
        'completed' => "🎉 <b>Configurazione completata, :name!</b>\n\n"
            . 'Corso impostato su: <b>:program</b>.',
        'ready' => 'Cosa vorresti fare?',
    ],
    'menu' => [
        'welcome' => "👋 <b>Benvenuto su PARSI Education Bot, :name!</b>\n\n"
            . "L'Associazione Studentesca PARSI è qui per supportarti con materiali didattici, servizi per gli studenti e guide universitarie.\n\n"
            . 'Quale servizio vorresti utilizzare oggi?',
        'choose_option' => "Scegli un'opzione dal menu sottostante, o invia /start.",
        'closed' => "<b>:lastSection</b>\n\nMenu chiuso.",
        'default_section' => 'Menu',
    ],
    'settings' => [
        'title' => "⚙️ <b>Impostazioni</b>\n\n"
            . "Lingua attuale: <b>:lang</b>\n"
            . "Corso di Laurea: <b>:program</b>\n\n"
            . 'Cosa desideri modificare?',
        'not_selected' => 'Non selezionato',
        'change_language' => '🌐 Cambia Lingua',
        'change_program' => '🎓 Cambia Corso di Laurea',
        'back_settings' => '⬅️ Torna alle Impostazioni',
        'change_language_prompt' => "🌐 <b>Select Preferred Language</b>\n\n"
            . "Please select your preferred language:\n"
            . "Seleziona la tua lingua preferita:\n"
            . 'لطفاً زبان مورد نظر خود را انتخاب کنید:',
        'change_program_prompt' => "🔄 <b>Seleziona Corso di Laurea</b>\n\nScegli un corso di laurea:",
    ],
    'sections' => [
        'educational_files' => '📚 Materiali Didattici',
        'settings' => '⚙️ Impostazioni',
        'edisu' => '🔍 Edisu',
        'deadlines' => '❓ Scadenze Importanti',
        'contact' => '📞 Contattaci',
        'report' => '⚠️ Segnalazioni',
        'tutorials' => '📑 Guide e Tutorial',
        'groups' => '👥 Gruppi Studenti',
        'edisu_coming_soon' => "🔍 <b>Edisu</b>\n\nLa guida Edisu sarà disponibile a breve! 🚀",
        'deadlines_coming_soon' => "❓ <b>Scadenze Importanti</b>\n\nLe scadenze accademiche saranno disponibili a breve! 🚀",
        'tutorials_coming_soon' => "📑 <b>Guide e Tutorial</b>\n\nLe guide e i tutorial PARSI saranno disponibili a breve! 🚀",
        'groups_coming_soon' => "👥 <b>Gruppi Studenti</b>\n\nI gruppi studenteschi per corsi di laurea saranno disponibili a breve! 🚀",
        'contact_coming_soon' => "⚠️ <b>Contatti / Segnalazioni</b>\n\nQuesta sezione sarà disponibile a breve! 🚀",
    ],
    'report' => [
        'hub_text' => "⚠️ <b>Centro Segnalazioni e Supporto</b>\n\n"
            . "Segnala un problema con un materiale didattico oppure invia un messaggio direttamente agli amministratori PARSI:\n\n"
            . "• Per segnalare un file danneggiato o errato, cercalo e premi <b>Segnala</b>.\n"
            . "• Per inviare una segnalazione scritta al team, premi <b>Invia Segnalazione agli Admin</b>.",
        'report_material_btn' => '🔍 Cerca Materiale da Segnalare',
        'send_report_btn' => '📝 Invia Segnalazione agli Admin',
    ],
    'tutorials' => [
        'title' => "📑 <b>Guide e Tutorial Didattici PARSI</b>\n\n"
            . "Consulta le guide e i tutorial pratici preparati dall'associazione PARSI per gli studenti del Politecnico di Torino.\n\n"
            . "Seleziona una guida per aprire il relativo post o documento:",
        'edisu_btn' => '🏛️ Guida Borsa di Studio e Alloggi EDISU',
        'enrollment_btn' => '🎓 Guida Immatricolazione e Visto',
        'didattica_btn' => '💻 Tutorial Portale della Didattica',
        'permesso_btn' => '🪪 Guida Permesso di Soggiorno',
        'channel_guides_btn' => '📢 Visualizza Tutte le Guide sul Canale PARSI',
    ],
    'education_hub' => [
        'title' => "📚 <b>Materiali Didattici</b>\n\n"
            . "Benvenuto nell'archivio didattico di PARSI per il Politecnico di Torino.\n\n"
            . "Qui puoi consultare gli appunti e materiali dei corsi, cercare tra tutte le materie, o inviare esami e dispense per aiutare altri studenti!\n\n"
            . "Cosa vorresti fare?",
        'browse_btn' => '📚 Visualizza Corsi e Materiali',
        'search_btn' => '🔍 Cerca Materiali',
        'submit_btn' => '📤 Invia Materiale Didattico',
    ],
    'submit_material' => [
        'prompt' => "📤 <b>Contribuisci con Materiale Didattico</b>\n\n"
            . "Aiuta gli studenti del PoliTo condividendo temi d'esame, appunti o riassunti!\n\n"
            . "Per inviare:\n"
            . "1. Premi <b>📚 Materiali Didattici</b>\n"
            . "2. Scegli Corso di Laurea e Insegnamento\n"
            . "3. Premi <b>📤 Invia Materiale per questo Corso</b>\n\n"
            . 'Gli amministratori del Comitato Didattico verificheranno e pubblicheranno il materiale! 🎓',
        'choose_course' => "📤 <b>Invia Materiale</b> — 🎓 <b>:program</b>\n\nScegli l'insegnamento per cui desideri caricare il materiale:",
        'choose_type' => "📤 <b>Invia Materiale</b> — 📚 <b>:course</b>\n\nScegli il tipo di materiale didattico che desideri inviare:",
        'course_prompt' => "📤 <b>Invia Materiale per :course</b>\n\n"
            . "Grazie per il tuo contributo a PARSI PoliTo!\n\n"
            . "📝 Invia prima un titolo per questo materiale, poi potrai caricare il/i file.\n\n"
            . 'Gli amministratori del Comitato Didattico verificheranno e pubblicheranno il file! 🎓',
        'file_received' => "📥 File ricevuto: <b>:filename</b>\n📁 File totali: <b>:count</b>\n\n💡 Puoi inviare altri file per questo materiale, oppure tocca <b>Conferma invio</b> per terminare.",
        'finish_button' => '✅ Conferma invio (:count)',
        'success' => "✅ <b>Grazie!</b>\n\n"
            . "Il tuo materiale per <b>:course</b> (:count file) è stato ricevuto ed è in fase di revisione.\n"
            . 'Una volta approvato dal comitato, sarà visibile a tutti gli studenti! 🎓',
        'no_course_selected' => "⚠️ <b>Nessun insegnamento selezionato!</b>\n\n"
            . "Premi <b>📤 Invia Materiale</b> e seleziona un insegnamento prima di inviare i file.",
        'cancelled' => "❌ <b>Invio annullato.</b>",
        'cancel' => '❌ Annulla',
        'back_to_edu' => '⬅️ Torna ai Materiali Didattici',
        'approved_notification' => "🎉 <b>Buone notizie!</b>\n\nIl tuo materiale per <b>:course</b> è stato approvato dal Comitato Didattico ed è ora disponibile sul bot!\n\nGrazie per il tuo contributo alla comunità studentesca PARSI! 🎓",
        'rejected_notification' => "❌ <b>Aggiornamento sulla tua proposta</b>\n\nIl tuo materiale per <b>:course</b> è stato esaminato dal Comitato Didattico e al momento non è stato possibile approvarlo per la pubblicazione.\n\nGrazie per la tua disponibilità e collaborazione! 🤝",
        'writing_title' => "Questo è il tuo titolo: <b>:title</b>\n\nSe desideri continuare, tocca <b>Sì</b> e invia il/i file desiderato/i.",
        'choose_title' => "⚠️ <b>Per favore, inserisci prima un titolo!</b>\n\nPrima di inviare i file, scrivi in questa chat un titolo descrittivo (ad es. <i>Esami 2024 con soluzioni</i>) e confermalo.",
        'title_approved' => "✅ <b>Titolo confermato!</b>\n\nOra puoi inviare il/i tuo/i file direttamente in questa chat (file singolo o album).",
        'title_rejected' => "✏️ <b>Nessun problema!</b>\n\nScrivi in questa chat il nuovo titolo che desideri utilizzare:",
        'waiting_for_files' => "📎 <b>In attesa dei file...</b>\n\nTitolo: <b>:title</b>\n\nInvia il/i tuo/i file (PDF, ZIP, DOCX o foto) in questa chat.",
    ],
    'courses' => [
        'choose_course' => "\nScegli un insegnamento per visualizzare i materiali:",
        'choose_program' => "📚 <b>Materiali Didattici</b>\n\nScegli il tuo corso di laurea:",
        'choose_year' => "🎓 <b>:program</b>\n\nScegli l'anno di corso per visualizzare gli insegnamenti:",
        'choose_track' => "🎯 <b>:program</b>\n\nScegli il tuo indirizzo di specializzazione:",
        'change_track' => '🎯 Cambia Indirizzo',
        'all_tracks' => '🌐 Tutti i percorsi (Mostra tutti i corsi)',
        'saved_track' => "✅ <i>Indirizzo salvato: :track</i>\n\n",
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
        'back_edu' => '⬅️ Torna ai Materiali Didattici',
    ],
    'tracks' => [
        'choose_track' => "🎯 <b>:program</b>\n\nScegli il tuo indirizzo di specializzazione:",
        'change_track' => '🎯 Cambia indirizzo',
        'all_tracks' => '🌐 Tutti gli indirizzi (Mostra tutti i corsi)',
        'saved_track' => "✅ <i>Indirizzo salvato: :track</i>\n\n",
        'back_to_programs' => '⬅️ Torna ai corsi di laurea',
    ],
    'resources' => [
        'not_available' => 'Questo materiale non è più disponibile.',
        'no_files' => 'Nessun file scaricabile al momento disponibile per questa risorsa.',
        'caption_header' => "📚 <b>:course</b>\n"
            . "📄 :title\n"
            . ":description"
            . '✅ <b>Verificato da PARSI</b>',
        'caption_contributor' => "\n⭐ Contributo di: :contributor",
        'caption_drive_link' => "\n\n🔗 <b>Google Drive:</b>\n:link",
        'caption_social_media' => "\n\n✨ In bocca al lupo per gli esami! 🎓\n\n"
            . "📢 @:channel\n"
            . "🤖 @:bot",
        'send_error' => 'Impossibile inviare il file. Riprova più tardi.',
        'partial_files_error' => 'Alcuni file non sono ancora disponibili su Telegram.',
        'download_drive' => '📥 Scarica da Google Drive',
        'download_file_drive' => '📥 Scarica :filename',
        'available_on_drive' => "📥 <b>Download da Google Drive</b>\n\nQuesto materiale è disponibile su Google Drive. Clicca sul pulsante qui sotto per visualizzarlo o scaricarlo nel browser:",
        'get_all_in_bot' => '📥 Scarica tutti i file nel Bot',
        'open_course_in_bot' => '📖 Apri insegnamento nel Bot',
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
        'too_short' => '⚠️ Inserisci almeno 3 caratteri.',
        'write_prompt' => "✍️ <b>Invia una Segnalazione al Team PARSI</b>\n\nDescrivi il problema, errore o anomalia riscontrata con questo materiale.\nI nostri amministratori verificheranno la segnalazione a breve:",
        'success' => "✅ <b>Grazie!</b> La tua segnalazione è stata inviata.\nIl nostro team la verificherà a breve.",
        'already_reported' => "ℹ️ Hai già segnalato questo materiale di recente. Lo stiamo verificando.",
        'cancel' => '❌ Annulla',
        'cancelled' => 'Segnalazione annullata.',
    ],
    'contact' => [
        'hub_text' => "📞 <b>Contattaci e Supporto Studenti</b>\n\n"
            . "Hai bisogno di aiuto, hai domande o vuoi segnalare un problema con i materiali didattici? Siamo a tua disposizione:\n\n"
            . "• <b>Scrivi ai Moderatori:</b> Invia le tue domande, suggerimenti o feedback tramite messaggio diretto al canale Telegram di PARSI.\n"
            . "• <b>Segnala un Materiale:</b> Se un file è danneggiato, errato o illeggibile, cercalo per inviare una segnalazione.\n"
            . "• <b>Canale Community:</b> Unisciti al nostro canale Telegram ufficiale per tutti gli aggiornamenti.",
        'too_short' => '⚠️ Inserisci almeno 3 caratteri.',
        'report_material_btn' => '🔍 Cerca Materiale da Segnalare',
        'send_message_btn' => '💬 Invia Messaggio ai Moderatori',
        'channel_btn' => '📢 Canale Telegram PARSI',
        'write_prompt' => "✍️ <b>Contatta il Team PARSI</b>\n\nScrivi qui sotto il tuo messaggio o richiesta.\nI nostri moderatori lo riceveranno e ti risponderanno al più presto:",
        'sent_success' => "✅ <b>Messaggio Inviato!</b>\n\nGrazie per averci contattato. Il team PARSI ha ricevuto il tuo messaggio.",
        'cancel' => '❌ Annulla',
        'cancelled' => 'Richiesta annullata.',
    ],
    'about' => [
        'text' => "<b>PARSI</b> è l’associazione degli studenti iraniani del <b>Politecnico di Torino</b>, nata con l’obiettivo di supportare, mettere in contatto e accompagnare gli studenti iraniani durante il loro percorso universitario.\n\n"
            . "🎯 <b>La nostra missione</b>\n"
            . "Cerchiamo di rendere più semplice la vita accademica e studentesca raccogliendo e condividendo <b>materiale didattico, appunti, prove d’esame, guide utili</b> e informazioni sulle principali <b>scadenze universitarie, EDISU e amministrative</b>.\n\n"
            . "🤝 <b>Creato dagli studenti, per gli studenti</b>\n"
            . "Gran parte dei materiali disponibili nel bot viene condivisa direttamente dagli studenti e, dopo essere stata revisionata e organizzata, viene messa a disposizione della comunità.\n\n"
            . "Questo bot nasce dalla collaborazione tra studenti, con l’obiettivo di rendere accessibili a tutti risorse, informazioni ed esperienze utili.\n\n"
            . "<b>Benvenuti nella comunità degli studenti iraniani del Politecnico di Torino.</b>",
        'channel_btn' => '📢 Canale Telegram Ufficiale',
        'insta_btn' => '📸 Pagina Instagram Ufficiale',
        'contact_btn' => '💬 Contatta il Team PARSI',
        'members_btn' => '👥 Membri del Team PARSI',
        'back_about' => '⬅️ Torna a Chi siamo',
        'members_text' => "👥 <b>Membri del Direttivo PARSI</b>\n\n"
            . "• <b>Kave Alikhani</b> — Presidente\n"
            . "• <b>Arya Gholami</b> — Vicepresidente\n"
            . "• <b>Anita Javidian</b> — Responsabile Comitato Media\n"
            . "• <b>Arya Mansouri</b> — Responsabile Finanziario\n"
            . "• <b>Nika Taherian</b> — Responsabile Comitato Culturale\n"
            . "• <b>Ali Arsalan</b> — Responsabile Comitato Esecutivo\n"
            . "• <b>Hamidreza Ramezani</b> — Responsabile Comitato Didattico\n",
    ],
    'rate_limit' => [
        'too_many_messages' => "⚠️ <b>Troppe Richieste</b>\n\nStai inviando messaggi troppo velocemente. Attendi <b>:seconds secondi</b> prima di riprovare.",
        'too_many_uploads' => "⚠️ <b>Limite di Caricamento Orario Raggiunto</b>\n\nPer prevenire abusi, ogni account studente può caricare fino a 20 file all'ora. Attendi <b>:minutes minuti</b> prima di inviare altro materiale.",
        'too_many_clicks' => '⚠️ Rallenta! Stai cliccando troppo rapidamente.',
    ],
    'file_security' => [
        'rejected' => "🛡️ <b>Avviso di Sicurezza: File Rifiutato</b>\n\nIl file <code>:filename</code> non può essere accettato per il seguente motivo:\n<b>:reason</b>\n\n📌 <i>Ti preghiamo di caricare esclusivamente materiale didattico valido (PDF, Word, PowerPoint o ZIP).</i>",
        'reasons' => [
            'dangerous_extension' => 'I file eseguibili o gli script sono severamente vietati per la sicurezza del sistema e degli studenti.',
            'unsupported_extension' => 'Formato non supportato. Carica un file PDF, Word, PowerPoint o ZIP.',
            'double_extension' => 'I file con doppia estensione o estensione camuffata sono bloccati per motivi di sicurezza.',
            'missing_extension' => 'Il file non ha un\'estensione valida.',
            'magic_bytes_mismatch' => 'La firma binaria del file non corrisponde alla sua estensione (possibile camuffamento).',
            'malicious_signature_detected' => 'Rilevata firma binaria eseguibile potenzialmente pericolosa nell\'intestazione del file.',
            'embedded_script_detected' => 'Rilevato codice o script incorporato nel file.',
            'virus_detected' => 'La scansione antivirus ha segnalato questo file come non sicuro.',
            'empty_file' => 'Il file caricato è vuoto (0 byte).',
            'default' => 'Il file non ha superato i controlli di sicurezza.',
        ],
    ],
];

