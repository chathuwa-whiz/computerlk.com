# Google Login සැකසීම (Setup Guide)

## 1. Google Cloud Console එකෙන් Project හදන්න

1. බ්‍රව්සර් එකෙන් යන්න: **https://console.cloud.google.com/**
2. Google account එකෙන් login වෙන්න.
3. **Create Project** (හෝ ඉහල project dropdown එකෙන් "New Project") click කරන්න.
4. Project name දාන්න (උදා: `Ecodez Store`), **Create** click කරන්න.

---

## 2. OAuth consent screen සකසන්න

1. වම් පැත්තේ **APIs & Services** → **OAuth consent screen** click කරන්න.
2. **External** (testing සඳහා) select කරන්න → **Create**.
3. අනිවාර්ය fields පුරවන්න:
   - **App name:** Ecodez Store (හෝ ඔයාගේ site name)
   - **User support email:** ඔයාගේ email
   - **Developer contact:** ඔයාගේ email
4. **Save and Continue** click කරන්න.
5. **Scopes** පිටුවේ **Add or Remove Scopes** click කරලා තියෙනවා නම් `email`, `profile`, `openid` add කරන්න (නැත්තං Skip).
6. **Save and Continue** → **Back to Dashboard**.

---

## 3. OAuth 2.0 Client ID හදන්න

1. වම් පැත්තේ **APIs & Services** → **Credentials** click කරන්න.
2. **+ Create Credentials** → **OAuth client ID** select කරන්න.
3. **Application type:** **Web application** select කරන්න.
4. **Name:** උදා: `Ecodez Web Client`.
5. **Authorized redirect URIs** යට තියෙන **+ ADD URI** click කරලා මේ link එක හරියටම type කරන්න:
   ```
   http://localhost/ecodestore/auth/google-callback.php
   ```
   (ඔයාගේ site තවත් URL එකක් run වෙනවා නම් ඒකත් add කරන්න, උදා: `http://127.0.0.1/ecodestore/auth/google-callback.php`)
6. **Create** click කරන්න.
7. පිටුවෙන් **Client ID** සහ **Client secret** පෙන්වනවා. මේ දෙක **copy** කරගෙන තියාගන්න (පස්සේ config.php එකට දාන්න).

---

## 4. config.php එකේ credentials දාන්න

1. Project එකේ **config.php** file එක open කරන්න.
2. මේ lines තියෙන තැන හොයාගෙන ඔයාගේ values දාන්න:

```php
// Google Sign-In (leave empty to hide Google login button)
define('GOOGLE_CLIENT_ID', 'ඔයාගේ-client-id.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'ඔයාගේ-client-secret');
define('GOOGLE_REDIRECT_URI', SITE_URL . '/auth/google-callback.php');
```

**උදාහරණය:**
```php
define('GOOGLE_CLIENT_ID', '123456789-abcdefg.apps.googleusercontent.com');
define('GOOGLE_CLIENT_SECRET', 'GOCSPX-xxxxxxxxxxxxxxxxxxxx');
```

3. File එක **save** කරන්න.

---

## 5. පරීක්ෂා කරන්න

1. Browser එකෙන් යන්න: `http://localhost/ecodestore/login.php`
2. **Continue with Google** button එක click කරන්න.
3. Google account එක select කරන්න (හෝ login වෙන්න).
4. Allow කරන්න කියලා ආයෙත් site එකට එනවා නම් Google login වැඩ කරනවා.

---

## ගැටලු තියෙනවා නම්

- **"Redirect URI mismatch"** – Google Console එකේ **Authorized redirect URIs** එක හරියට `http://localhost/ecodestore/auth/google-callback.php` විදිහට දාලා තියෙනවද බලන්න (https නොදාන්න localhost සඳහා).
- **"Access blocked"** – OAuth consent screen එක **Testing** mode එකේ තියෙනවා නම්, **Test users** එකට ඔයාගේ Gmail add කරලා save කරන්න.
- **"Google login is not configured"** – config.php එකේ `GOOGLE_CLIENT_ID` සහ `GOOGLE_CLIENT_SECRET` හරියට දාලා save කරලා තියෙනවද බලන්න.
