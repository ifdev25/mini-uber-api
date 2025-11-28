# Configuration de l'envoi d'emails - Guide rapide

Ce guide vous aide à configurer l'envoi d'emails pour le système de vérification d'email de Mini Uber API.

## Configuration rapide (5 minutes)

### Option 1 : Gmail (Recommandé pour débuter)

**Prérequis :**
- Compte Gmail avec validation en 2 étapes activée
- Bridge Gmail installé

**Installation du bridge Gmail :**
```bash
composer require symfony/google-mailer
```

1. **Créer un mot de passe d'application Gmail :**
   - Aller sur https://myaccount.google.com/apppasswords
   - Se connecter avec votre compte Gmail
   - Créer un nouveau mot de passe d'application
   - Nommer le "Mini Uber API"
   - Copier le mot de passe généré (16 caractères sans espaces)

2. **Configurer `.env.local` :**
   ```env
   MAILER_DSN=gmail+smtp://votre-email@gmail.com:votre-mot-de-passe-app@default
   MAILER_FROM_EMAIL=votre-email@gmail.com
   MAILER_FROM_NAME="Mini Uber"
   FRONTEND_URL=http://localhost:3000
   ```

3. **Exemple concret :**
   ```env
   MAILER_DSN=gmail+smtp://john.doe@gmail.com:abcdefghijklmnop@default
   MAILER_FROM_EMAIL=john.doe@gmail.com
   MAILER_FROM_NAME="Mini Uber"
   ```

4. **Tester :**
   ```bash
   curl -X POST http://localhost:8000/api/register \
     -H "Content-Type: application/json" \
     -d '{
       "email": "votre-email-test@gmail.com",
       "password": "password123",
       "firstName": "Test",
       "lastName": "User",
       "phone": "+33612345678",
       "userType": "passenger"
     }'
   ```

5. **Vérifier :** Vous devriez recevoir un email dans votre boîte Gmail

---

### Option 2 : Mailtrap (Recommandé pour développement)

**Avantage :** Capture les emails sans les envoyer réellement (idéal pour tests)

1. **Créer un compte gratuit :**
   - Aller sur https://mailtrap.io
   - S'inscrire gratuitement
   - Créer une inbox

2. **Copier les identifiants SMTP :**
   - Dans votre inbox Mailtrap, aller dans "SMTP Settings"
   - Sélectionner "Symfony 5+"
   - Copier le DSN fourni

3. **Configurer `.env.local` :**
   ```env
   MAILER_DSN=smtp://1a2b3c4d5e6f7g:9h8i7j6k5l4m3n@smtp.mailtrap.io:2525
   MAILER_FROM_EMAIL=noreply@mini-uber.com
   MAILER_FROM_NAME="Mini Uber"
   FRONTEND_URL=http://localhost:3000
   ```

4. **Tester :** Même commande curl que pour Gmail

5. **Vérifier :** Les emails apparaîtront dans votre inbox Mailtrap

---

### Option 3 : Mode développement sans envoi (Null)

Pour tester sans configurer de SMTP :

```env
MAILER_DSN=null://null
MAILER_FROM_EMAIL=noreply@mini-uber.com
MAILER_FROM_NAME="Mini Uber"
FRONTEND_URL=http://localhost:3000
```

Les emails ne seront pas envoyés mais loggés dans les logs Symfony.

**Voir les logs :**
```bash
symfony server:log
```

**Récupérer le token de vérification depuis la base de données :**
```bash
php bin/console doctrine:query:sql "SELECT email, verification_token FROM \"user\" WHERE email = 'test@example.com'"
```

---

## Vérification que tout fonctionne

### 1. Vider le cache Symfony

```bash
php bin/console cache:clear
```

### 2. S'inscrire avec l'API

```bash
curl -X POST http://localhost:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "firstName": "Test",
    "lastName": "User",
    "phone": "+33612345678",
    "userType": "passenger"
  }'
```

### 3. Vérifier la réception de l'email

- **Gmail :** Vérifier votre boîte de réception
- **Mailtrap :** Vérifier votre inbox Mailtrap
- **Null :** Vérifier les logs avec `symfony server:log`

### 4. Cliquer sur le lien de vérification

Le lien sera au format :
```
http://localhost:3000/verify-email?token=abc123def456...
```

---

## Troubleshooting

### Erreur : "Connection refused"

**Cause :** Le serveur SMTP est inaccessible

**Solution :**
- Vérifiez que le port est correct (587 pour Gmail/SMTP standard)
- Vérifiez votre pare-feu
- Testez avec `MAILER_DSN=null://null` pour voir si le problème vient du SMTP

### Erreur : "Authentication failed"

**Cause :** Identifiants SMTP incorrects

**Solution :**
- Pour Gmail : utilisez un **mot de passe d'application**, pas votre mot de passe normal
- Pour Mailtrap : vérifiez que vous avez copié les bons identifiants
- Vérifiez qu'il n'y a pas d'espaces dans le DSN

### Erreur : "Username and Password not accepted"

**Cause :** Gmail n'accepte pas le mot de passe

**Solution :**
1. Vérifiez que la validation en 2 étapes est activée sur votre compte Google
2. Régénérez un nouveau mot de passe d'application
3. Utilisez le nouveau mot de passe dans `MAILER_DSN`

### L'email n'arrive pas

**Solution :**
1. Vérifiez les logs Symfony :
   ```bash
   symfony server:log
   ```

2. Vérifiez votre dossier spam/courrier indésirable

3. Testez avec Mailtrap pour confirmer que le code fonctionne

4. Pour Gmail : vérifiez les "Activités inhabituelles" dans votre compte Google

---

## Configuration avancée

### Autres providers SMTP

**SendGrid :**
```env
MAILER_DSN=smtp://apikey:YOUR_API_KEY@smtp.sendgrid.net:587
```

**Mailgun :**
```env
MAILER_DSN=smtp://postmaster@yourdomain.com:password@smtp.mailgun.org:587
```

**Amazon SES :**
```env
MAILER_DSN=smtp://AKIAIOSFODNN7EXAMPLE:wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY@email-smtp.us-east-1.amazonaws.com:587
```

### Personnaliser le template d'email

Modifier `src/Service/EmailService.php` ligne 72-119 pour changer le design de l'email.

---

## Checklist de configuration

- [ ] `MAILER_DSN` configuré dans `.env.local`
- [ ] `MAILER_FROM_EMAIL` configuré
- [ ] `MAILER_FROM_NAME` configuré (optionnel)
- [ ] `FRONTEND_URL` configuré
- [ ] Cache Symfony vidé (`php bin/console cache:clear`)
- [ ] Test d'inscription effectué
- [ ] Email reçu et vérifié

---

## Support

Si vous rencontrez des problèmes :

1. Vérifiez les logs : `symfony server:log`
2. Testez avec `MAILER_DSN=null://null`
3. Consultez la documentation Symfony Mailer : https://symfony.com/doc/current/mailer.html
4. Ouvrir une issue sur GitHub : https://github.com/ifdev25/mini-uber-api/issues
