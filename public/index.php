<?php
declare(strict_types=1);

require dirname(__DIR__) . '/config/bootstrap.php';

use TinyPHP\Database\Database;
use TinyPHP\Http\Request;
use TinyPHP\Security\Csrf;

$request = Request::capture();
$notice = '';
$error = '';

if ($request->isPost()) {
    if (!Csrf::verify($request->post('_token')->string())) {
        http_response_code(419);
        $error = 'Your form session expired. Refresh the page and try again.';
    } else {
        $name = $request->post('name')->trim()->string();
        $email = $request->post('email')->trim()->string();
        $message = $request->post('message')->trim()->string();

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $message === '') {
            $error = 'Please enter your name, a valid email and a message.';
        } else {
            try {
                Database::connect()->table('messages')->insert([
                    'name' => $name,
                    'email' => $email,
                    'message' => $message,
                ]);
                $notice = 'Message saved to MySQL.';
                Csrf::regenerate();
            } catch (Throwable $exception) {
                error_log($exception->getMessage());
                $error = 'Database unavailable. Check your .env settings and import database/schema.sql.';
            }
        }
    }
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>tinyPHP starter</title>
    <style>
        :root { color-scheme: dark; font-family: Inter, ui-sans-serif, system-ui, sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 28px; background: #0b1020; color: #e8edf8; }
        main { width: min(100%, 620px); padding: 34px; border: 1px solid #27324a; border-radius: 22px; background: #111a2d; box-shadow: 0 24px 80px #0005; }
        .tag { color: #7dd3fc; font-size: 12px; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
        h1 { margin: 10px 0; font-size: clamp(2.2rem, 8vw, 4rem); letter-spacing: -.06em; }
        p { color: #aab7ce; line-height: 1.6; }
        label { display: block; margin: 18px 0 7px; color: #cbd5e1; font-size: 14px; }
        input, textarea { width: 100%; padding: 13px 14px; border: 1px solid #34415c; border-radius: 10px; background: #0b1222; color: white; font: inherit; }
        textarea { min-height: 110px; resize: vertical; }
        button { margin-top: 20px; border: 0; border-radius: 10px; padding: 13px 18px; background: #38bdf8; color: #082032; font-weight: 800; cursor: pointer; }
        .notice { padding: 12px 14px; border-radius: 10px; background: #12372f; color: #9df3d5; }
        .error { padding: 12px 14px; border-radius: 10px; background: #48252b; color: #ffc2c7; }
        code { color: #7dd3fc; }
        footer { margin-top: 25px; color: #71809b; font-size: 12px; }
    </style>
</head>
<body>
<main>
    <div class="tag">Small PHP toolkit · big clarity</div>
    <h1>tinyPHP<span style="color:#38bdf8">.</span></h1>
    <p>A tiny, modular wrapper for requests, form handling, secure uploads and MySQL. Submit this form to test the request and database layers.</p>

    <?php if ($notice !== ''): ?><div class="notice"><?= e($notice) ?></div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="/">
        <input type="hidden" name="_token" value="<?= e(Csrf::token()) ?>">
        <label for="name">Name</label>
        <input id="name" name="name" required maxlength="100" autocomplete="name">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" required maxlength="190" autocomplete="email">
        <label for="message">Message</label>
        <textarea id="message" name="message" required></textarea>
        <button type="submit">Save message</button>
    </form>

    <footer>PHP <?= e(PHP_VERSION) ?> · No framework · PDO prepared statements</footer>
</main>
</body>
</html>
