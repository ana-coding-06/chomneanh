<?php
// ════════════════════════════════════════════════════════════════════════════
//  theme.php — shared <head>, Duolingo-inspired design tokens, and brand mark
// ════════════════════════════════════════════════════════════════════════════
function renderHead(string $title): void { ?>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
  <!-- Nunito = rounded friendly body; Baloo 2 = chunky display, Duolingo-like -->
  <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@600;700;800&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.31.0/dist/tabler-icons.min.css"/>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: {
            display: ['"Baloo 2"','"Nunito"','system-ui','sans-serif'],
            sans:    ['Nunito','system-ui','sans-serif'],
          },
          colors: {
            // Duolingo palette
            grass:   { DEFAULT:'#58CC02', dark:'#58A700', light:'#89E219' },
            sky:     { DEFAULT:'#1CB0F6', dark:'#1899D6' },
            bee:     { DEFAULT:'#FFC800', dark:'#E6A100' },
            cardinal:{ DEFAULT:'#FF4B4B', dark:'#E63F3F' },
            beetle:  { DEFAULT:'#CE82FF', dark:'#A560E8' },
            ink:     '#3C3C3C',
            wolf:    '#777777',
            swan:    '#E5E5E5',
            polar:   '#F7F7F7',
          },
          keyframes: {
            floaty: { '0%,100%':{transform:'translateY(0)'}, '50%':{transform:'translateY(-8px)'} },
            popin:  { '0%':{transform:'scale(0.9)',opacity:'0'}, '100%':{transform:'scale(1)',opacity:'1'} },
          },
          animation: { floaty:'floaty 4s ease-in-out infinite', popin:'popin .4s cubic-bezier(0.34,1.56,0.64,1)' },
        }
      }
    }
  </script>
  <style>
    body { font-family:'Nunito',sans-serif; color:#3C3C3C; -webkit-tap-highlight-color:transparent; }
    .font-display { font-family:'Baloo 2',sans-serif; }
    ::-webkit-scrollbar { width:10px; height:10px; }
    ::-webkit-scrollbar-thumb { background:#E5E5E5; border-radius:10px; }

    /* ── Signature Duolingo 3D press buttons ───────────────────────────── */
    .btn3d {
      border:none; border-radius:16px; font-weight:800; letter-spacing:.6px;
      text-transform:uppercase; cursor:pointer; transition:transform .06s, box-shadow .06s, filter .15s;
      font-family:'Nunito',sans-serif;
    }
    .btn3d:active { transform:translateY(4px); box-shadow:none !important; }
    .btn-grass  { background:#58CC02; color:#fff; box-shadow:0 4px 0 #58A700; }
    .btn-grass:hover  { filter:brightness(1.05); }
    .btn-sky    { background:#1CB0F6; color:#fff; box-shadow:0 4px 0 #1899D6; }
    .btn-white  { background:#fff; color:#1CB0F6; box-shadow:0 4px 0 #E5E5E5; border:2px solid #E5E5E5; }
    .btn-white:hover  { background:#F7F7F7; }
    .btn-disabled { background:#E5E5E5; color:#AFAFAF; box-shadow:0 4px 0 #D0D0D0; cursor:not-allowed; }
    .btn-disabled:active { transform:none; box-shadow:0 4px 0 #D0D0D0 !important; }

    /* chunky card with bottom shadow, Duolingo style */
    .card3d { background:#fff; border:2px solid #E5E5E5; border-radius:10px; box-shadow:0 2px 0 #E5E5E5; }

    .auth-bg { background:#fff; }
  </style>
<?php }

// Brand mark: uses uploaded logo if present, else a rounded grass-green flame.
function brandMark(int $size = 44): string {
    $logo = __DIR__ . '/chomneanh.png';
    if (file_exists($logo)) {
        return '<img src="chomneanh.png" alt="Chomneanh logo" style="height:'.$size.'px;width:auto;object-fit:contain;"/>';
    }
    $fs = (int)round($size*0.55);
    return '<span style="display:grid;place-items:center;height:'.$size.'px;width:'.$size.'px;border-radius:'.( $size*0.28 ).'px;background:#58CC02;box-shadow:0 4px 0 #58A700;">
              <i class="ti ti-flame" style="font-size:'.$fs.'px;color:#fff;"></i>
            </span>';
}
