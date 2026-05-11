<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Abonnement actif</title>
<style>
  body { margin:0; padding:0; background:#f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width:580px; margin:32px auto; }
  .header { background:#1e40af; border-radius:12px 12px 0 0; padding:32px 40px; text-align:center; }
  .header h1 { margin:0; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:-0.5px; }
  .header p { margin:4px 0 0; color:#bfdbfe; font-size:14px; }
  .body { background:#ffffff; padding:40px; }
  .body h2 { margin:0 0 8px; font-size:20px; color:#1e293b; }
  .body p { margin:0 0 16px; font-size:15px; color:#475569; line-height:1.6; }
  .badge { display:inline-block; background:#dcfce7; color:#166534;
           padding:4px 12px; border-radius:99px; font-size:13px; font-weight:600; margin-bottom:20px; }
  .btn { display:block; width:fit-content; margin:24px auto; padding:14px 32px;
         background:#2563eb; color:#ffffff !important; text-decoration:none;
         border-radius:8px; font-weight:600; font-size:15px; text-align:center; }
  .footer { background:#f8fafc; border-radius:0 0 12px 12px; padding:20px 40px;
            text-align:center; font-size:12px; color:#94a3b8; }
</style>
</head>
<body>
<div class="wrapper">

  <div class="header">
    <h1>LP<span style="color:#93c5fd">Abonnements</span></h1>
    <p>Gestion d'abonnements publications professionnelles</p>
  </div>

  <div class="body">
    <span class="badge">✓ Abonnement actif</span>
    <h2>Votre abonnement numérique est activé</h2>
    <p>
      Bonjour {{ $client->first_name }},<br>
      Un nouvel abonnement numérique vient d'être ajouté à votre compte.
      Vous pouvez dès maintenant accéder à vos publications depuis votre espace personnel.
    </p>

    <a href="{{ $portalUrl }}" class="btn">Accéder à mon espace →</a>

    <p style="font-size:13px; color:#94a3b8; text-align:center; margin-top:8px;">
      Connectez-vous avec votre email : <strong>{{ $client->email }}</strong>
    </p>
  </div>

  <div class="footer">
    © {{ date('Y') }} {{ config('app.name') }} — Cet email vous a été envoyé suite à l'activation de votre abonnement.
  </div>

</div>
</body>
</html>
