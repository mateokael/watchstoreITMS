<?php
// Bad / foul word filter for reviews (Tagalog, Bisaya and English).
// Set before including: $badText (what the customer typed)
// After including:      $badFound   = array of the bad words found, exactly as typed (empty array = clean)
//                       $badMessage = the sentence that explains why the review was not accepted ('' when clean)
//
// How it works (regex = regular expression):
//   1. make a lowercase copy of the text and swap look-alike symbols:  @ -> a, 0 -> o, 1 -> i, 3 -> e, $ -> s ...
//   2. for every bad word build a regex where each letter may repeat:  gago -> g+a+g+o+   (so "gagooo" is caught too)
//   3. $badAnywhere words are caught even inside a longer word ("clusterfuck", "putanginamo")
//      $badWholeWord words must stand alone, so normal words like "class" or "assess" are NOT blocked
//
// Left out on purpose because they block normal sentences: gay, queer, hell, bloody, cracker.
$badAnywhere = [
    // Tagalog
    'putangina', 'tangina', 'taena', 'putang', 'putsa', 'namputsa', 'punyeta', 'pakshet', 'bolshet', 'hinayupak',
    'tarantado', 'walanghiya', 'langhiya', 'kapalngmukha', 'buwisit', 'hesusmaryosep', 'anakngputa', 'anakngputsa',
    'ihodeputa', 'yudeputa', 'pukenginamo', 'utenngama', 'utennibapa', 'putangama',
    // phrases with spaces or a dash (a space/dash in the list matches the same space/dash in the review)
    'walang-hiya', 'walang hiya', 'kapal ng mukha', 'putang ina', 'anak ng puta', 'anak ng putsa', 'iho de puta', 'puke ng ina mo', 'uten ng ama mo', 'uten ni bapa mo',
    // Bisaya
    'pisteng', 'yawa',
    // English profanity
    'fuck', 'shit', 'cunt', 'bitch', 'bullshit', 'asshole', 'asshat', 'assmunch', 'arsehole', 'dumbass', 'dickhead', 'bollocks',
    'blowjob', 'cocksucker', 'motherfucker', 'cornhole', 'wanker', 'whore', 'slut', 'twat',
    // English slurs
    'nigger', 'nigga', 'faggot', 'retard', 'coonass', 'wetback', 'chingchong', 'pajeet',
];
$badWholeWord = [
    // Tagalog
    'gago', 'gaga', 'gagi', 'ogag', 'ogags', 'hayop', 'letse', 'leche', 'lintik', 'tae', 'shet', 'pakyu', 'puta', 'puke', 'kupal', 'supot',
    // Bisaya
    'piste', 'bayot', 'buang',
    // English
    'ass', 'arse', 'bastard', 'bugger', 'cock', 'crap', 'damn', 'dick', 'feck', 'piss', 'poof', 'poofter', 'prick', 'pussy', 'jackass',
    'paki', 'spic', 'kike', 'hori',
];
$badAllowed = ['shiitake', 'scunthorpe'];          // innocent words that contain a blocked word

$badFound = [];
$badClean = strtr(strtolower($badText), ['@' => 'a', '4' => 'a', '0' => 'o', '1' => 'i', '3' => 'e', '$' => 's', '5' => 's', '7' => 't', '!' => 'i', '+' => 't']);
foreach ($badAllowed as $badOk) {                   // hide the allowed words (same length, so positions stay the same)
    $badClean = preg_replace_callback('/' . $badOk . '/', fn($m) => str_repeat('_', strlen($m[0])), $badClean);
}
foreach (['anywhere' => $badAnywhere, 'whole' => $badWholeWord] as $badKind => $badList) {
    foreach ($badList as $badWord) {
        $badPattern = '';
        foreach (str_split($badWord) as $badLetter) {
            $badPattern .= $badLetter . '+';
        }
        if ($badKind === 'whole') {
            $badPattern = '(?<![a-z0-9])' . $badPattern . '(?![a-z0-9])';
        }
        if (preg_match_all('/' . $badPattern . '/', $badClean, $badHits, PREG_OFFSET_CAPTURE)) {
            foreach ($badHits[0] as $badHit) {
                $badFound[$badHit[1]] = substr($badText, $badHit[1], strlen($badHit[0]));   // the word as the person typed it
            }
        }
    }
}
$badFound = array_values($badFound);

$badMessage = '';
if ($badFound) {
    $badShown = '';
    foreach (array_slice($badFound, 0, 5) as $badW) {          // show p*******a, never the whole word
        $badShown .= ($badShown ? ', ' : '') . (strlen($badW) <= 2 ? str_repeat('*', strlen($badW)) : substr($badW, 0, 1) . str_repeat('*', strlen($badW) - 2) . substr($badW, -1));
    }
    $badMessage = 'Your review was not saved because it contains language that is not allowed (' . $badShown . '). '
        . 'WatchStore reviews must stay respectful so other buyers can trust them. Insults, curses and slurs, in Tagalog, Bisaya or English, '
        . 'are blocked, even with symbols or numbers in place of letters. Please reword your comment and submit again.';
}
