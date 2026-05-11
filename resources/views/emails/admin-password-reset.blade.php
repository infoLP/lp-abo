<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <style>
    body { font-family: Arial, sans-serif; background:#f4f4f4; margin:0; padding:0; }
    .container { max-width:560px; margin:40px auto; background:#ffffff;
                 border-radius:8px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,.08); }
    .header { background:#1a1a1a; padding:28px 32px; text-align:center; }
    .header h1 { color:#ffffff; font-size:20px; margin:0; }
    .body { padding:32px; color:#333333; font-size:15px; line-height:1.7; }
    .btn { display:inline-block; margin:24px 0; padding:14px 32px;
           background:#2563eb; color:#ffffff !important; text-decoration:none;
           border-radius:6px; font-weight:bold; font-size:15px; }
    .note { font-size:13px; color:#6b7280; margin-top:20px; }
    .footer { background:#f9fafb; padding:16px 32px; text-align:center;
              font-size:12px; color:#9ca3af; border-top:1px solid #e5e7eb; }
  </style>
</head>
<body>
<div class="container">
  <div class="header">
    <h1>LPAbonnements</h1>
  </div>
  <div class="body">
    <p>Bonjour {{ $user->first_name ?: $user->name }},</p>
    <p>
      Un administrateur a demandé la réinitialisation de votre mot de passe.<br>
      Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :
    </p>
    <p style="text-align:center;">
      <a href="{{ $resetUrl }}" class="btn">Réinitialiser mon mot de passe</a>
    </p>
    <p class="note">
      Ce lien est valable <strong>60 minutes</strong>.<br>
      Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.<br>
      Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :<br>
      <span style="word-break:break-all;color:#2563eb;">{{ $resetUrl }}</span>
    </p>
  </div>
  <div class="footer">
    LPAbonnements — Cet email a été envoyé automatiquement, merci de ne pas y répondre.
  </div>
</div>
</body>
</html>
