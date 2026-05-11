<?php
$file = '/var/www/html/app/Filament/Resources/ClientResource/Pages/EditClient.php';
$content = file_get_contents($file);

// Remplacer plan_id par subscription_plan_id
$content = str_replace(
    "'plan_id'          => \$plan->id,",
    "'subscription_plan_id' => \$plan->id,",
    $content
);

// Vérifier aussi order_id — voir si la colonne existe
file_put_contents($file, $content);
exec('php -l ' . $file, $output, $code);
echo implode("\n", $output) . "\n";
echo "Code retour : $code\n";
