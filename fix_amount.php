<?php
$file = __DIR__ . '/app/Services/ClientImportService.php';
$content = file_get_contents($file);

// Ancien code
$old = "'amount_paid'=>!empty(\$data['amount'])?(float)str_replace(',','.',\$data['amount']):\$plan->price";

// Nouveau code avec validation > 0
$new = "'amount_paid'=>(!empty(\$data['amount']) && (float)str_replace(',','.',\$data['amount']) > 0) ? (float)str_replace(',','.',\$data['amount']) : \$plan->price";

if (strpos($content, $old) === false) {
    echo "⚠️  Chaîne non trouvée — vérifier manuellement\n";
    exit(1);
}

$content = str_replace($old, $new, $content);
file_put_contents($file, $content);
echo "✅ Correction appliquée ligne 236\n";
