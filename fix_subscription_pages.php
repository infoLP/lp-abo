<?php
$file = '/var/www/html/app/Filament/Resources/SubscriptionResource.php';
$content = file_get_contents($file);

// Supprimer la route 'create' de getPages
$content = preg_replace(
    "/\s*'create'\s*=>\s*Pages\\\\CreateSubscription::route\([^)]+\),?\n?/",
    "\n",
    $content
);

// S'assurer que getPages ne contient que index et edit
if (strpos($content, 'getPages') !== false) {
    $content = preg_replace(
        '/public static function getPages\(\): array\s*\{.*?\}/s',
        "public static function getPages(): array
    {
        return [
            'index' => Pages\ListSubscriptions::route('/'),
            'edit'  => Pages\EditSubscription::route('/{record}/edit'),
        ];
    }",
        $content
    );
}

file_put_contents($file, $content);
exec('php -l ' . $file, $output, $code);
echo implode("\n", $output) . "\n";
echo "Code retour : $code\n";
