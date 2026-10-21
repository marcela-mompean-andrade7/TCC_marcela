<?php
require_once __DIR__ . '/config.php';
$errors = [];
$submittedNeeds = $_POST['accessibility_needs'] ?? [];
if (!is_array($submittedNeeds)) {
  $submittedNeeds = [];
}
$accessibilityNeeds = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  check_csrf();
  $nameValue = $_POST['name'] ?? '';
  $emailValue = $_POST['email'] ?? '';
  $passwordValue = $_POST['password'] ?? '';
  $roleValue = $_POST['role'] ?? '';
  $serieValue = $_POST['serie'] ?? null;
  $regionValue = $_POST['regiao'] ?? '';
  $schoolValue = $_POST['escola'] ?? '';
  $name = is_string($nameValue) ? trim($nameValue) : '';
  $email = is_string($emailValue) ? trim($emailValue) : '';
  $password = is_string($passwordValue) ? $passwordValue : '';
  $role = is_string($roleValue) ? $roleValue : '';
  $serie = is_string($serieValue) ? $serieValue : null;
  $regiao = is_string($regionValue) ? $regionValue : '';
  $escola = is_string($schoolValue) ? trim($schoolValue) : '';
  $preferredLanguage = $_POST['preferred_language'] ?? 'pt-BR';
  $disabilityStatus = $_POST['disability_status'] ?? 'prefer_not';
  $colorVisionStatus = $_POST['color_vision_status'] ?? 'prefer_not';
  $disabilityOtherValue = $_POST['disability_other'] ?? '';
  $disabilityOther = is_string($disabilityOtherValue) ? trim($disabilityOtherValue) : '';
  $accessibilityOtherValue = $_POST['accessibility_other'] ?? '';
  $accessibilityOther = is_string($accessibilityOtherValue) ? trim($accessibilityOtherValue) : '';
  $submittedNeedsValue = $_POST['accessibility_needs'] ?? [];
  $submittedNeeds = is_array($submittedNeedsValue) ? $submittedNeedsValue : [];
  $accessibilityPaletteValue = $_POST['accessibility_palette'] ?? '';
  $accessibilityPalette = is_string($accessibilityPaletteValue) ? $accessibilityPaletteValue : '';
  $accessibilityNeeds = [];
  if (!is_string($preferredLanguage)) $preferredLanguage = '';
  if (!is_string($disabilityStatus)) $disabilityStatus = '';
  if (!is_string($colorVisionStatus)) $colorVisionStatus = '';
  if (mb_strlen($name) < 2) $errors[] = 'Informe um nome válido.';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Informe um e-mail válido.';
  if (strlen($password) < 8) $errors[] = 'A senha precisa ter pelo menos 8 caracteres.';
  if (!in_array($role, ['aluno', 'professor'], true)) $errors[] = 'Escolha aluno ou professor.';
  if ($role === 'aluno' && !in_array($serie, ['6º ano', '7º ano', '8º ano', '9º ano'], true)) $errors[] = 'Escolha sua série.';
  if (!in_array($regiao, ['Norte', 'Nordeste', 'Centro-Oeste', 'Sudeste', 'Sul'], true)) $errors[] = 'Escolha sua região.';
  if ($escola === '') $errors[] = 'Informe sua escola.';
  if (!array_key_exists($preferredLanguage, language_options())) $errors[] = 'Escolha um idioma válido.';
  if (!array_key_exists($disabilityStatus, accessibility_status_options())) $errors[] = 'Escolha uma opção válida para deficiência.';
  if (!array_key_exists($colorVisionStatus, color_vision_status_options())) $errors[] = 'Escolha uma opção válida para percepção das cores.';
  if (!is_string($accessibilityPaletteValue)) $errors[] = 'Escolha uma paleta de cores válida.';
  if ($accessibilityPalette !== '' && !array_key_exists($accessibilityPalette, accessibility_palette_options())) {
    $errors[] = 'Escolha uma paleta de cores válida.';
  }
  if ($disabilityStatus === 'other' && mb_strlen($disabilityOther) > 255) $errors[] = 'A descrição da deficiência deve ter até 255 caracteres.';
  if (!is_array($submittedNeedsValue)) {
    $errors[] = 'Selecione recursos de acessibilidade válidos.';
  } elseif (
    !array_is_list($submittedNeeds)
    || count(array_filter($submittedNeeds, 'is_string')) !== count($submittedNeeds)
    || array_diff($submittedNeeds, array_keys(accessibility_resource_options()))
    || count(array_intersect($submittedNeeds, array_keys(accessibility_palette_options()))) > 1
  ) {
    $errors[] = 'Selecione recursos de acessibilidade válidos.';
  } else {
    $accessibilityNeeds = array_values(array_unique($submittedNeeds));
  }
  if ($accessibilityPalette !== '' && array_key_exists($accessibilityPalette, accessibility_palette_options())) {
    $accessibilityNeeds = array_values(array_diff($accessibilityNeeds, array_keys(accessibility_palette_options())));
    $accessibilityNeeds[] = $accessibilityPalette;
  }
  if (in_array('other', $accessibilityNeeds, true) && mb_strlen($accessibilityOther) > 255) {
    $errors[] = 'A descrição dos recursos deve ter até 255 caracteres.';
  }
  if (array_key_exists($preferredLanguage, language_options())) {
    set_language($preferredLanguage);
  }
  if (!$errors) {
    $q = $pdo->prepare('SELECT id FROM users WHERE email=?');
    $q->execute([$email]);
    if ($q->fetch()) $errors[] = 'Este e-mail já está cadastrado.';
    else {
      $q = $pdo->prepare(
        'INSERT INTO users(name,email,password_hash,role,serie,regiao,escola,preferred_language,disability_status,disability_other,color_vision_status,accessibility_needs,accessibility_other)
         VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?)'
      );
      $q->execute([
        $name,
        $email,
        password_hash($password, PASSWORD_DEFAULT),
        $role,
        $role === 'aluno' ? $serie : null,
        $regiao,
        $escola,
        $preferredLanguage,
        $disabilityStatus,
        $disabilityStatus === 'other' && $disabilityOther !== '' ? $disabilityOther : null,
        $colorVisionStatus,
        $accessibilityNeeds ? json_encode($accessibilityNeeds, JSON_THROW_ON_ERROR) : null,
        in_array('other', $accessibilityNeeds, true) && $accessibilityOther !== '' ? $accessibilityOther : null,
      ]);
      redirect('login.php?ok=1');
    }
  }
}
?>
<!doctype html>
<html lang="<?= e(current_language()) ?>" <?= accessibility_html_attributes() ?>>

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= e(t('Criar conta')) ?> — Lunaris Academy</title>
  <link rel="stylesheet" href="<?= e(application_asset_url('css/style.css')) ?>">
  <script src="<?= e(application_asset_url('js/theme.js')) ?>"></script>
</head>

<body>
  <main class="auth card register-page"><a href="index.php"><?= e(t('← Início')) ?></a>
    <h1><?= e(t('Criar conta')) ?></h1>
    <p><?= e(t('Preencha seus dados para entrar na Lunaris Academy.')) ?></p>
    <?php foreach ($errors as $x): ?><div class="alert" role="alert"><?= e(t($x)) ?></div><?php endforeach; ?>
    <form method="post"><input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
      <label><?= e(t('Nome')) ?><input name="name" required maxlength="120" value="<?= e(is_string($_POST['name'] ?? null) ? $_POST['name'] : '') ?>"></label>
      <label><?= e(t('E-mail')) ?><input type="email" name="email" required value="<?= e(is_string($_POST['email'] ?? null) ? $_POST['email'] : '') ?>"></label>
      <label><?= e(t('Senha')) ?><input type="password" name="password" minlength="8" required></label>
      <label><?= e(t('Tipo de conta')) ?><select name="role" id="role" required>
          <option value=""><?= e(t('Selecione')) ?></option>
          <option value="aluno"<?= ($_POST['role'] ?? '') === 'aluno' ? ' selected' : '' ?>><?= e(t('Aluno')) ?></option>
          <option value="professor"<?= ($_POST['role'] ?? '') === 'professor' ? ' selected' : '' ?>><?= e(t('Professor')) ?></option>
        </select></label>
      <label id="serieField"><?= e(t('Série')) ?><select name="serie">
          <option value=""><?= e(t('Selecione')) ?></option>
          <?php foreach (['6º ano', '7º ano', '8º ano', '9º ano'] as $grade): ?>
            <option value="<?= e($grade) ?>"<?= ($_POST['serie'] ?? '') === $grade ? ' selected' : '' ?>><?= e(t($grade)) ?></option>
          <?php endforeach; ?>
        </select></label>
      <label><?= e(t('Região')) ?><select name="regiao" required>
          <option value=""><?= e(t('Selecione')) ?></option>
          <?php foreach (['Norte', 'Nordeste', 'Centro-Oeste', 'Sudeste', 'Sul'] as $region): ?>
            <option value="<?= e($region) ?>"<?= ($_POST['regiao'] ?? '') === $region ? ' selected' : '' ?>><?= e(t($region)) ?></option>
          <?php endforeach; ?>
        </select></label>
      <label><?= e(t('Escola')) ?><input name="escola" required maxlength="180" value="<?= e(is_string($_POST['escola'] ?? null) ? $_POST['escola'] : '') ?>"></label>

      <fieldset class="signup-preference-section">
        <legend><?= e(t('Acessibilidade e necessidades especiais')) ?></legend>
        <p><?= e(t('Você possui algum tipo de deficiência?')) ?></p>
        <label><?= e(t('Selecione uma opção')) ?>
          <select name="disability_status" id="disabilityStatus" aria-describedby="disabilityPrivacy">
            <?php foreach (accessibility_status_options() as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= ($_POST['disability_status'] ?? 'prefer_not') === $value ? ' selected' : '' ?>><?= e(t($label)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <p id="disabilityPrivacy" class="signup-privacy-note"><?= e(t('Essa informação é privada, opcional e não afeta seu acesso às atividades ou recursos da plataforma.')) ?></p>
        <label><?= e(t('Você tem daltonismo ou dificuldade para distinguir determinadas cores?'))
          ?><select name="color_vision_status" aria-describedby="colorVisionPrivacy">
            <?php foreach (color_vision_status_options() as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= ($_POST['color_vision_status'] ?? 'prefer_not') === $value ? ' selected' : '' ?>><?= e(t($label)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <p id="colorVisionPrivacy" class="signup-privacy-note"><?= e(t('Esta resposta não é um diagnóstico médico. Ela serve apenas para orientar os recursos visuais que você pode escolher.')) ?></p>
        <div id="disabilityOtherField"<?= ($_POST['disability_status'] ?? 'prefer_not') === 'other' ? '' : ' hidden' ?>>
          <label><?= e(t('Se desejar, descreva a deficiência')) ?>
            <input name="disability_other" maxlength="255" value="<?= e(is_string($_POST['disability_other'] ?? null) ? $_POST['disability_other'] : '') ?>">
          </label>
        </div>
        <fieldset id="accessibilityNeedsField" class="signup-resource-options">
          <legend><?= e(t('Quais recursos de acessibilidade poderiam ajudar? (opcional)')) ?></legend>
          <?php foreach (accessibility_resource_options() as $value => $label): ?>
            <label class="signup-checkbox">
              <input type="checkbox" name="accessibility_needs[]" value="<?= e($value) ?>"<?= in_array($value, $accessibilityNeeds, true) ? ' checked' : '' ?>>
              <span><?= e(t($label)) ?></span>
            </label>
          <?php endforeach; ?>
          <label for="registrationAccessibilityPalette"><?= e(t('Paleta alternativa de cores')) ?>
            <select id="registrationAccessibilityPalette" name="accessibility_palette">
              <option value=""><?= e(t('Padrão')) ?></option>
              <?php foreach (accessibility_palette_options() as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= ($_POST['accessibility_palette'] ?? '') === $value ? ' selected' : '' ?>><?= e(t($label)) ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label id="accessibilityOtherField"<?= in_array('other', $accessibilityNeeds, true) ? '' : ' hidden' ?>>
            <?= e(t('Se desejar, descreva outros recursos')) ?>
            <input name="accessibility_other" maxlength="255" value="<?= e(is_string($_POST['accessibility_other'] ?? null) ? $_POST['accessibility_other'] : '') ?>">
          </label>
        </fieldset>
      </fieldset>

      <fieldset class="signup-preference-section">
        <legend><?= e(t('Idioma da plataforma')) ?></legend>
        <label><?= e(t('Qual idioma você prefere utilizar?')) ?>
          <select name="preferred_language">
            <?php foreach (language_options() as $value => $label): ?>
              <option value="<?= e($value) ?>"<?= ($_POST['preferred_language'] ?? 'pt-BR') === $value ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </fieldset>

      <button class="btn full" type="submit"><?= e(t('Cadastrar')) ?></button>
    </form>
    <p><?= e(t('Já possui conta?')) ?> <a href="login.php"><?= e(t('Entrar')) ?></a></p>
  </main>
  <script>
    const r = document.getElementById('role'),
      s = document.getElementById('serieField'),
      disability = document.getElementById('disabilityStatus'),
      otherField = document.getElementById('disabilityOtherField'),
      needsField = document.getElementById('accessibilityNeedsField'),
      otherNeedsField = document.getElementById('accessibilityOtherField');

    function f() {
      s.style.display = r.value === 'professor' ? 'none' : 'grid'
    }
    r.addEventListener('change', f);
    f();

    function updateAccessibilityFields() {
      otherField.hidden = disability.value !== 'other';
      otherNeedsField.hidden = !needsField.querySelector('input[value="other"]').checked;
    }

    disability.addEventListener('change', updateAccessibilityFields);
    needsField.addEventListener('change', updateAccessibilityFields);
    updateAccessibilityFields();
  </script>
</body>

</html>