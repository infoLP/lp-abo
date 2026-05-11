<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bienvenue</title>
<style>
  body { margin:0; padding:0; background:#f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
  .wrapper { max-width:580px; margin:32px auto; }
  .header { background:#1e40af; border-radius:12px 12px 0 0; padding:32px 40px; text-align:center; }
  .header h1 { margin:0; color:#ffffff; font-size:24px; font-weight:700; letter-spacing:-0.5px; }
  .header p { margin:4px 0 0; color:#bfdbfe; font-size:14px; }
  .body { background:#ffffff; padding:40px; }
  .body h2 { margin:0 0 8px; font-size:20px; color:#1e293b; }
  .body p { margin:0 0 16px; font-size:15px; color:#475569; line-height:1.6; }
  .btn { display:block; width:fit-content; margin:24px auto; padding:14px 32px;
         background:#2563eb; color:#ffffff !important; text-decoration:none;
         border-radius:8px; font-weight:600; font-size:15px; text-align:center; }
  .btn:hover { background:#1d4ed8; }
  .note { background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;
          padding:16px; font-size:13px; color:#64748b; margin-top:24px; }
  .note a { color:#2563eb; word-break:break-all; }
  .divider { border:none; border-top:1px solid #e2e8f0; margin:24px 0; }
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
    <h2>Bienvenue, {{ $client->first_name }} !</h2>
    <p>
      Votre abonnement numérique vient d'être activé. Pour accéder à votre espace personnel
      et consulter vos publications, vous devez d'abord créer votre mot de passe.
    </p>

    <p>Ce lien est valable <strong>24 heures</strong>.</p>

    <a href="{{ $activationUrl }}" class="btn">Créer mon mot de passe →</a>

    <hr class="divider">

    <p style="font-size:14px; color:#64748b;">
      Après activation, vous aurez accès à :
    </p>
    <ul style="font-size:14px; color:#475569; line-height:2; padding-left:20px; margin:0 0 16px;">
      <li>Vos abonnements et leur statut</li>
      <li>La lecture en ligne de vos publications</li>
      <li>La gestion de votre profil</li>
    </ul>

    <div class="note">
      <strong>Le lien ne fonctionne pas ?</strong><br>
      Copiez cette adresse dans votre navigateur :<br>
      <a href="{{ $activationUrl }}">{{ $activationUrl }}</a>
    </div>
  </div>

  <div class="footer">
    © {{ date('Y') }} {{ config('app.name') }} — Cet email vous a été envoyé suite à la création de votre abonnement.
  </div>

</div>
</body>
</html>
