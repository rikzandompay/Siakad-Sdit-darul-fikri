// SisforSinta Mobile UI Canvas Generator for Figma
// Generates native vector frames, typography, components, and auto-layouts

async function generateSisforSintaDesign() {
  await figma.loadFontAsync({ family: "Inter", style: "Regular" });
  await figma.loadFontAsync({ family: "Inter", style: "Medium" });
  await figma.loadFontAsync({ family: "Inter", style: "Semi Bold" });
  await figma.loadFontAsync({ family: "Inter", style: "Bold" });

  const page = figma.currentPage;

  const C = {
    brandDark: { r: 0.05, g: 0.36, b: 0.23 },      // #0D5C3A
    brandLight: { r: 0.09, g: 0.64, b: 0.29 },     // #16A34A
    brandBg: { r: 0.94, g: 0.99, b: 0.96 },        // #F0FDF4
    bg: { r: 0.97, g: 0.98, b: 0.99 },             // #F8FAFC
    white: { r: 1, g: 1, b: 1 },
    textDark: { r: 0.09, g: 0.12, b: 0.17 },       // #0F172A
    textMuted: { r: 0.40, g: 0.47, b: 0.58 },      // #64748B
    border: { r: 0.89, g: 0.91, b: 0.94 },         // #E2E8F0
    amber: { r: 0.96, g: 0.62, b: 0.04 },          // #F59E0B
    amberBg: { r: 1, g: 0.98, b: 0.92 },
    red: { r: 0.88, g: 0.19, b: 0.21 },
  };

  const W = 390;
  const H = 844;
  let startX = 1600; // Place next to the Code layer

  function makeFrame(name, x) {
    const f = figma.createFrame();
    f.name = name;
    f.resize(W, H);
    f.x = x;
    f.y = 0;
    f.fills = [{ type: 'SOLID', color: C.bg }];
    f.cornerRadius = 32;
    f.clipsContent = true;
    page.appendChild(f);
    return f;
  }

  function addStatusBar(parent, bg) {
    const bar = figma.createFrame();
    bar.resize(W, 44);
    bar.fills = [{ type: 'SOLID', color: bg }];
    const t = figma.createText();
    t.characters = "07:30";
    t.fontName = { family: "Inter", style: "Semi Bold" };
    t.fontSize = 12;
    t.fills = [{ type: 'SOLID', color: C.white }];
    t.x = 24;
    t.y = 14;
    bar.appendChild(t);
    parent.appendChild(bar);
  }

  function addBottomNav(parent, activeIndex, items) {
    const nav = figma.createFrame();
    nav.resize(W, 64);
    nav.y = H - 64;
    nav.fills = [{ type: 'SOLID', color: C.white }];
    const border = figma.createFrame();
    border.resize(W, 1);
    border.fills = [{ type: 'SOLID', color: C.border }];
    nav.appendChild(border);

    const step = W / items.length;
    items.forEach((item, i) => {
      const label = figma.createText();
      label.characters = item;
      label.fontName = { family: "Inter", style: i === activeIndex ? "Bold" : "Medium" };
      label.fontSize = 10;
      label.fills = [{ type: 'SOLID', color: i === activeIndex ? C.brandDark : C.textMuted }];
      label.x = i * step + (step - label.width) / 2;
      label.y = 26;
      nav.appendChild(label);
    });
    parent.appendChild(nav);
  }

  // SCREEN 1: LOGIN DUAL-ROLE
  const s1 = makeFrame("01. Login (Dual-Role: Guru & Wali)", startX);
  addStatusBar(s1, C.white);
  const logo = figma.createFrame();
  logo.resize(64, 64);
  logo.x = (W - 64) / 2;
  logo.y = 100;
  logo.cornerRadius = 16;
  logo.fills = [{ type: 'SOLID', color: C.brandDark }];
  s1.appendChild(logo);

  const logoText = figma.createText();
  logoText.characters = "DF";
  logoText.fontName = { family: "Inter", style: "Bold" };
  logoText.fontSize = 24;
  logoText.fills = [{ type: 'SOLID', color: C.white }];
  logoText.x = logo.x + 16;
  logoText.y = logo.y + 18;
  s1.appendChild(logoText);

  const title = figma.createText();
  title.characters = "SisforSinta Mobile";
  title.fontName = { family: "Inter", style: "Bold" };
  title.fontSize = 18;
  title.fills = [{ type: 'SOLID', color: C.textDark }];
  title.x = (W - title.width) / 2;
  title.y = 180;
  s1.appendChild(title);

  const sub = figma.createText();
  sub.characters = "Sistem Informasi SDIT Darul Fikri";
  sub.fontName = { family: "Inter", style: "Regular" };
  sub.fontSize = 12;
  sub.fills = [{ type: 'SOLID', color: C.textMuted }];
  sub.x = (W - sub.width) / 2;
  sub.y = 208;
  s1.appendChild(sub);

  // Tab Login
  const tabBox = figma.createFrame();
  tabBox.resize(342, 40);
  tabBox.x = 24;
  tabBox.y = 248;
  tabBox.cornerRadius = 12;
  tabBox.fills = [{ type: 'SOLID', color: { r: 0.94, g: 0.95, b: 0.96 } }];
  s1.appendChild(tabBox);

  const tab1 = figma.createFrame();
  tab1.resize(168, 34);
  tab1.x = 27;
  tab1.y = 251;
  tab1.cornerRadius = 10;
  tab1.fills = [{ type: 'SOLID', color: C.white }];
  s1.appendChild(tab1);

  const tab1T = figma.createText();
  tab1T.characters = "Guru & Staf";
  tab1T.fontName = { family: "Inter", style: "Bold" };
  tab1T.fontSize = 12;
  tab1T.fills = [{ type: 'SOLID', color: C.brandDark }];
  tab1T.x = tab1.x + (168 - tab1T.width) / 2;
  tab1T.y = 260;
  s1.appendChild(tab1T);

  const tab2T = figma.createText();
  tab2T.characters = "Wali Murid";
  tab2T.fontName = { family: "Inter", style: "Medium" };
  tab2T.fontSize = 12;
  tab2T.fills = [{ type: 'SOLID', color: C.textMuted }];
  tab2T.x = 200 + (168 - tab2T.width) / 2;
  tab2T.y = 260;
  s1.appendChild(tab2T);

  // Input Fields
  function drawInput(parent, labelText, valText, y) {
    const lbl = figma.createText();
    lbl.characters = labelText;
    lbl.fontName = { family: "Inter", style: "Semi Bold" };
    lbl.fontSize = 11;
    lbl.fills = [{ type: 'SOLID', color: C.textDark }];
    lbl.x = 24;
    lbl.y = y;
    parent.appendChild(lbl);

    const box = figma.createFrame();
    box.resize(342, 44);
    box.x = 24;
    box.y = y + 20;
    box.cornerRadius = 12;
    box.fills = [{ type: 'SOLID', color: C.white }];
    box.strokes = [{ type: 'SOLID', color: C.border }];
    parent.appendChild(box);

    const val = figma.createText();
    val.characters = valText;
    val.fontName = { family: "Inter", style: "Regular" };
    val.fontSize = 13;
    val.fills = [{ type: 'SOLID', color: C.textDark }];
    val.x = 36;
    val.y = y + 33;
    parent.appendChild(val);
  }

  drawInput(s1, "Username / NIP", "ahmad_fauzi", 310);
  drawInput(s1, "Kata Sandi", "••••••••", 388);

  const btnLogin = figma.createFrame();
  btnLogin.resize(342, 48);
  btnLogin.x = 24;
  btnLogin.y = 472;
  btnLogin.cornerRadius = 12;
  btnLogin.fills = [{ type: 'SOLID', color: C.brandDark }];
  s1.appendChild(btnLogin);

  const btnLoginT = figma.createText();
  btnLoginT.characters = "Masuk ke Aplikasi";
  btnLoginT.fontName = { family: "Inter", style: "Bold" };
  btnLoginT.fontSize = 13;
  btnLoginT.fills = [{ type: 'SOLID', color: C.white }];
  btnLoginT.x = btnLogin.x + (342 - btnLoginT.width) / 2;
  btnLoginT.y = btnLogin.y + 16;
  s1.appendChild(btnLoginT);

  // SCREEN 2: GURU DASHBOARD
  const s2 = makeFrame("02. Guru - Dashboard & Jadwal", startX + 430);
  addStatusBar(s2, C.brandDark);

  const gHead = figma.createFrame();
  gHead.resize(W, 160);
  gHead.y = 44;
  gHead.fills = [{ type: 'SOLID', color: C.brandDark }];
  s2.appendChild(gHead);

  const gGreeting = figma.createText();
  gGreeting.characters = "Selamat Mengajar,";
  gGreeting.fontName = { family: "Inter", style: "Regular" };
  gGreeting.fontSize = 11;
  gGreeting.fills = [{ type: 'SOLID', color: { r: 0.7, g: 0.9, b: 0.8 } }];
  gGreeting.x = 24;
  gGreeting.y = 56;
  s2.appendChild(gGreeting);

  const gName = figma.createText();
  gName.characters = "Ust. Ahmad Fauzi, S.Pd.";
  gName.fontName = { family: "Inter", style: "Bold" };
  gName.fontSize = 15;
  gName.fills = [{ type: 'SOLID', color: C.white }];
  gName.x = 24;
  gName.y = 74;
  s2.appendChild(gName);

  // Stat Card
  const statBox = figma.createFrame();
  statBox.resize(342, 68);
  statBox.x = 24;
  statBox.y = 110;
  statBox.cornerRadius = 14;
  statBox.fills = [{ type: 'SOLID', color: { r: 1, g: 1, b: 1 }, opacity: 0.15 }];
  s2.appendChild(statBox);

  const statT1 = figma.createText();
  statT1.characters = "Kehadiran Hari Ini: 92.4% (170 Hadir, 8 Sakit, 5 Izin, 4 Alpa)";
  statT1.fontName = { family: "Inter", style: "Semi Bold" };
  statT1.fontSize = 11;
  statT1.fills = [{ type: 'SOLID', color: C.white }];
  statT1.x = 36;
  statT1.y = 134;
  s2.appendChild(statT1);

  // Jadwal Mengajar Card
  const jCard = figma.createFrame();
  jCard.resize(342, 84);
  jCard.x = 24;
  jCard.y = 224;
  jCard.cornerRadius = 16;
  jCard.fills = [{ type: 'SOLID', color: C.white }];
  jCard.strokes = [{ type: 'SOLID', color: C.border }];
  s2.appendChild(jCard);

  const jMapel = figma.createText();
  jMapel.characters = "Kelas 5A • Matematika";
  jMapel.fontName = { family: "Inter", style: "Bold" };
  jMapel.fontSize = 13;
  jMapel.fills = [{ type: 'SOLID', color: C.textDark }];
  jMapel.x = 40;
  jMapel.y = 240;
  s2.appendChild(jMapel);

  const jJam = figma.createText();
  jJam.characters = "⏰ 07:30 - 09:00 WIB • Ruang Kelas 5A";
  jJam.fontName = { family: "Inter", style: "Regular" };
  jJam.fontSize = 11;
  jJam.fills = [{ type: 'SOLID', color: C.textMuted }];
  jJam.x = 40;
  jJam.y = 264;
  s2.appendChild(jJam);

  const btnAbsen = figma.createFrame();
  btnAbsen.resize(90, 32);
  btnAbsen.x = 260;
  btnAbsen.y = 250;
  btnAbsen.cornerRadius = 8;
  btnAbsen.fills = [{ type: 'SOLID', color: C.brandDark }];
  s2.appendChild(btnAbsen);

  const btnAbsenT = figma.createText();
  btnAbsenT.characters = "Mulai Absen";
  btnAbsenT.fontName = { family: "Inter", style: "Semi Bold" };
  btnAbsenT.fontSize = 10;
  btnAbsenT.fills = [{ type: 'SOLID', color: C.white }];
  btnAbsenT.x = btnAbsen.x + (90 - btnAbsenT.width) / 2;
  btnAbsenT.y = 260;
  s2.appendChild(btnAbsenT);

  addBottomNav(s2, 0, ["Beranda", "Presensi", "Sholat", "Tugas", "Izin"]);

  // SCREEN 3: WALI DASHBOARD
  const s3 = makeFrame("03. Wali Murid - Perkembangan Anak", startX + 860);
  addStatusBar(s3, C.brandDark);

  const wHead = figma.createFrame();
  wHead.resize(W, 160);
  wHead.y = 44;
  wHead.fills = [{ type: 'SOLID', color: C.brandDark }];
  s3.appendChild(wHead);

  const wGreeting = figma.createText();
  wGreeting.characters = "Perkembangan Ananda:";
  wGreeting.fontName = { family: "Inter", style: "Regular" };
  wGreeting.fontSize = 11;
  wGreeting.fills = [{ type: 'SOLID', color: { r: 0.7, g: 0.9, b: 0.8 } }];
  wGreeting.x = 24;
  wGreeting.y = 56;
  s3.appendChild(wGreeting);

  const wName = figma.createText();
  wName.characters = "Muhammad Zaki Pratama";
  wName.fontName = { family: "Inter", style: "Bold" };
  wName.fontSize = 15;
  wName.fills = [{ type: 'SOLID', color: C.white }];
  wName.x = 24;
  wName.y = 74;
  s3.appendChild(wName);

  // Status Anak Card
  const wStatBox = figma.createFrame();
  wStatBox.resize(342, 68);
  wStatBox.x = 24;
  wStatBox.y = 110;
  wStatBox.cornerRadius = 14;
  wStatBox.fills = [{ type: 'SOLID', color: { r: 1, g: 1, b: 1 }, opacity: 0.15 }];
  s3.appendChild(wStatBox);

  const wStatT1 = figma.createText();
  wStatT1.characters = "✓ Hadir di Kelas  •  ✓ Sholat Dhuha  •  ⏳ Sholat Zuhur";
  wStatT1.fontName = { family: "Inter", style: "Semi Bold" };
  wStatT1.fontSize = 11;
  wStatT1.fills = [{ type: 'SOLID', color: C.white }];
  wStatT1.x = 36;
  wStatT1.y = 134;
  s3.appendChild(wStatT1);

  // PR Reminder Card
  const prCard = figma.createFrame();
  prCard.resize(342, 88);
  prCard.x = 24;
  prCard.y = 224;
  prCard.cornerRadius = 16;
  prCard.fills = [{ type: 'SOLID', color: C.amberBg }];
  prCard.strokes = [{ type: 'SOLID', color: { r: 0.99, g: 0.90, b: 0.70 } }];
  s3.appendChild(prCard);

  const prTitle = figma.createText();
  prTitle.characters = "📌 Pengingat Tugas / PR (Besok Dikumpulkan)";
  prTitle.fontName = { family: "Inter", style: "Bold" };
  prTitle.fontSize = 11;
  prTitle.fills = [{ type: 'SOLID', color: { r: 0.57, g: 0.31, b: 0.05 } }];
  prTitle.x = 38;
  prTitle.y = 238;
  s3.appendChild(prTitle);

  const prDesc = figma.createText();
  prDesc.characters = "Matematika: Latihan 3 Halaman 45 (Ust. Ahmad Fauzi)";
  prDesc.fontName = { family: "Inter", style: "Semi Bold" };
  prDesc.fontSize = 12;
  prDesc.fills = [{ type: 'SOLID', color: C.textDark }];
  prDesc.x = 38;
  prDesc.y = 258;
  s3.appendChild(prDesc);

  const prNote = figma.createText();
  prNote.characters = "Mohon bimbingan orang tua saat belajar malam ini.";
  prNote.fontName = { family: "Inter", style: "Regular" };
  prNote.fontSize = 10;
  prNote.fills = [{ type: 'SOLID', color: C.textMuted }];
  prNote.x = 38;
  prNote.y = 280;
  s3.appendChild(prNote);

  addBottomNav(s3, 0, ["Ananda", "Kehadiran", "Rapor", "Jadwal", "Surat Izin"]);

  // SCREEN 4: WALI NILAI RAPOR
  const s4 = makeFrame("04. Wali Murid - Hasil Studi (Rapor)", startX + 1290);
  addStatusBar(s4, C.white);

  const rHead = figma.createText();
  rHead.characters = "Hasil Studi / Nilai Rapor";
  rHead.fontName = { family: "Inter", style: "Bold" };
  rHead.fontSize = 16;
  rHead.fills = [{ type: 'SOLID', color: C.textDark }];
  rHead.x = 24;
  rHead.y = 60;
  s4.appendChild(rHead);

  const rCardTotal = figma.createFrame();
  rCardTotal.resize(342, 80);
  rCardTotal.x = 24;
  rCardTotal.y = 96;
  rCardTotal.cornerRadius = 16;
  rCardTotal.fills = [{ type: 'SOLID', color: C.brandDark }];
  s4.appendChild(rCardTotal);

  const rTotalT = figma.createText();
  rTotalT.characters = "Rata-rata Semester: 87.25 (Predikat A - Tuntas)";
  rTotalT.fontName = { family: "Inter", style: "Bold" };
  rTotalT.fontSize = 12;
  rTotalT.fills = [{ type: 'SOLID', color: C.white }];
  rTotalT.x = 40;
  rTotalT.y = 126;
  s4.appendChild(rTotalT);

  function addMapelRow(parent, name, nilai, pred, y) {
    const row = figma.createFrame();
    row.resize(342, 60);
    row.x = 24;
    row.y = y;
    row.cornerRadius = 14;
    row.fills = [{ type: 'SOLID', color: C.white }];
    row.strokes = [{ type: 'SOLID', color: C.border }];
    parent.appendChild(row);

    const n = figma.createText();
    n.characters = name;
    n.fontName = { family: "Inter", style: "Semi Bold" };
    n.fontSize = 12;
    n.fills = [{ type: 'SOLID', color: C.textDark }];
    n.x = 38;
    n.y = y + 15;
    parent.appendChild(n);

    const sc = figma.createText();
    sc.characters = nilai + " (" + pred + ")";
    sc.fontName = { family: "Inter", style: "Bold" };
    sc.fontSize = 13;
    sc.fills = [{ type: 'SOLID', color: C.brandDark }];
    sc.x = 280;
    sc.y = y + 15;
    parent.appendChild(sc);

    const sub = figma.createText();
    sub.characters = "Formatif BAB 1-4 Tuntas • SAS: 88.0";
    sub.fontName = { family: "Inter", style: "Regular" };
    sub.fontSize = 10;
    sub.fills = [{ type: 'SOLID', color: C.textMuted }];
    sub.x = 38;
    sub.y = y + 34;
    parent.appendChild(sub);
  }

  addMapelRow(s4, "Pendidikan Agama Islam", "92.0", "A", 192);
  addMapelRow(s4, "Matematika", "86.5", "A", 264);
  addMapelRow(s4, "Bahasa Arab", "88.0", "A", 336);
  addMapelRow(s4, "Tematik Terpadu", "85.0", "A", 408);

  addBottomNav(s4, 2, ["Ananda", "Kehadiran", "Rapor", "Jadwal", "Surat Izin"]);

  // Zoom to fit newly generated frames
  figma.viewport.scrollAndZoomIntoView([s1, s2, s3, s4]);
}

generateSisforSintaDesign();

