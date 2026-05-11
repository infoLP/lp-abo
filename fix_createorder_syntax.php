<?php
$file = '/var/www/html/app/Filament/Resources/OrderResource/Pages/CreateOrder.php';
$content = file_get_contents($file);

// Remplacer le bloc livraison mal formé
$old = '$delivAddr = $ship->default_delivery_address ?? $ship->default_billing_address;
            $deliveryData = [
                \'delivery_company\'     => $delivAddr?->l1 ?? \'\',
                \'delivery_recipient\'   => $delivAddr?->l1 ?? \'\',
                \'delivery_address1\'    => $delivAddr?->l4 ?? \'\',
                \'delivery_address2\'    => $delivAddr?->l5 ?? \'\',
                \'delivery_address3\'    => \'\',
                \'delivery_postal_code\' => $delivAddr?->l6_postal_code ?? \'\',
                \'delivery_city\'        => $delivAddr?->l6_city ?? \'\',
                \'delivery_cedex\'       => $delivAddr?->l6_cedex ?? \'\',
                \'delivery_country\'     => $delivAddr?->l7_country ?? \'FR\',
            ];';

$new = '$delivAddr = $ship->default_delivery_address ?? $ship->default_billing_address;
        $delivery = [
            \'delivery_company\'     => $delivAddr?->l1 ?? \'\',
            \'delivery_recipient\'   => $delivAddr?->l1 ?? \'\',
            \'delivery_address1\'    => $delivAddr?->l4 ?? \'\',
            \'delivery_address2\'    => $delivAddr?->l5 ?? \'\',
            \'delivery_address3\'    => \'\',
            \'delivery_postal_code\' => $delivAddr?->l6_postal_code ?? \'\',
            \'delivery_city\'        => $delivAddr?->l6_city ?? \'\',
            \'delivery_cedex\'       => $delivAddr?->l6_cedex ?? \'\',
            \'delivery_country\'     => $delivAddr?->l7_country ?? \'FR\',
        ];';

$content = str_replace($old, $new, $content);

file_put_contents($file, $content);

exec('php -l ' . $file, $output, $code);
echo implode("\n", $output) . "\n";
echo "Code retour : $code\n";
