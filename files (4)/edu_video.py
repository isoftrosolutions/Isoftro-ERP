#!/usr/bin/env python3
"""
Educational Reel: नेपालमा सरकार कसरी बन्छ?
30-second vertical reel | 1080x1920 | 30fps
Styles: Step-by-step arrows, Flashcard Q&A, Before/After, animated steps
"""

import os, math, random
from PIL import Image, ImageDraw, ImageFont

# ── constants ──────────────────────────────────────────────────────────
W, H   = 1080, 1920
FPS    = 30
FRAMES_DIR = "/home/claude/edu_frames"
os.makedirs(FRAMES_DIR, exist_ok=True)

# Colors
BLACK   = (0,   0,   0)
WHITE   = (255, 255, 255)
LIME    = (181, 236, 66)
ORANGE  = (255, 107,  0)
BLUE    = (41,  182, 246)
DARK    = (15,  15,  15)
CARD_BG = (22,  22,  22)
GREY    = (80,  80,  80)
RED     = (255, 70,  70)
GOLD    = (255, 200,  0)
GREEN   = (72,  199, 116)

# Fonts
FONT_BOLD = "/usr/share/fonts/truetype/freefont/FreeSansBold.ttf"
FONT_REG  = "/usr/share/fonts/truetype/freefont/FreeSans.ttf"
POPPINS_B = "/usr/share/fonts/truetype/google-fonts/Poppins-Bold.ttf"

def font(size, bold=True):
    try:
        return ImageFont.truetype(POPPINS_B if bold else FONT_REG, size)
    except:
        return ImageFont.truetype(FONT_BOLD if bold else FONT_REG, size)

def nfont(size):
    """Nepali font — FreeSans handles Devanagari well"""
    return ImageFont.truetype(FONT_BOLD, size)

# ── easing ────────────────────────────────────────────────────────────
def ease_out(t):     return 1 - (1-t)**3
def ease_in(t):      return t**3
def ease_in_out(t):  return 3*t*t - 2*t*t*t
def elastic_out(t):
    if t == 0 or t == 1: return t
    return pow(2, -10*t) * math.sin((t*10 - 0.75) * (2*math.pi/3)) + 1
def lerp(a, b, t):   return a + (b-a)*t
def clamp(v, lo=0.0, hi=1.0): return max(lo, min(hi, v))

# ── helpers ────────────────────────────────────────────────────────────
def new_frame(bg=BLACK):
    return Image.new("RGB", (W, H), bg)

def draw_text_c(draw, text, cx, cy, fnt, color, anchor="mm"):
    draw.text((cx, cy), text, font=fnt, fill=color, anchor=anchor)

def draw_shadow(draw, text, cx, cy, fnt, color, shadow=8):
    shadow_col = tuple(max(0, c//4) for c in color)
    draw.text((cx+shadow, cy+shadow), text, font=fnt, fill=shadow_col, anchor="mm")
    draw.text((cx, cy), text, font=fnt, fill=color, anchor="mm")

def text_w(draw, text, fnt):
    bb = draw.textbbox((0,0), text, font=fnt, anchor="lt")
    return bb[2]-bb[0]

def text_h(draw, text, fnt):
    bb = draw.textbbox((0,0), text, font=fnt, anchor="lt")
    return bb[3]-bb[1]

def draw_rounded_rect(draw, x0, y0, x1, y1, radius, fill=None, outline=None, width=3):
    draw.rounded_rectangle([x0, y0, x1, y1], radius=radius, fill=fill, outline=outline, width=width)

def draw_arrow_down(draw, cx, y_top, length, color, width=8, head=30):
    """Draw a downward arrow"""
    draw.line([(cx, y_top), (cx, y_top+length)], fill=color, width=width)
    draw.polygon([
        (cx, y_top+length+head),
        (cx-head//2, y_top+length),
        (cx+head//2, y_top+length)
    ], fill=color)

def draw_arrow_right(draw, x_left, cy, length, color, width=6, head=25):
    draw.line([(x_left, cy), (x_left+length, cy)], fill=color, width=width)
    draw.polygon([
        (x_left+length+head, cy),
        (x_left+length, cy-head//2),
        (x_left+length, cy+head//2)
    ], fill=color)

def draw_step_circle(draw, cx, cy, r, num, active=True):
    col = LIME if active else GREY
    draw.ellipse([cx-r, cy-r, cx+r, cy+r], fill=col, outline=WHITE, width=3)
    f = font(r-10)
    draw_text_c(draw, str(num), cx, cy, f, BLACK if active else WHITE)

def gradient_bg(img, color1, color2):
    """Top-to-bottom gradient"""
    pixels = img.load()
    for y in range(H):
        t = y / H
        r = int(lerp(color1[0], color2[0], t))
        g = int(lerp(color1[1], color2[1], t))
        b = int(lerp(color1[2], color2[2], t))
        for x in range(W):
            pixels[x, y] = (r, g, b)
    return img

def bg_dots(draw, alpha=0.3):
    """Subtle dot grid background"""
    spacing = 60
    for x in range(0, W, spacing):
        for y in range(0, H, spacing):
            a = int(255 * alpha * 0.15)
            draw.ellipse([x-2, y-2, x+2, y+2], fill=(a, a, a))

# ══════════════════════════════════════════════════════════════════════
# SCENE 1: HOOK — Flashcard Q&A style (0–4s)
# "नेपालमा सरकार कसरी बन्छ? के थाहा छ?"
# ══════════════════════════════════════════════════════════════════════
def scene_hook(img, t):
    draw = ImageDraw.Draw(img)
    bg_dots(draw)

    # Animated background glow
    glow_r = int(300 + 50*math.sin(t*math.pi*3))
    for i in range(3):
        a = int(8 - i*2)
        r = glow_r + i*40
        draw.ellipse([W//2-r, 400-r, W//2+r, 400+r],
                     fill=tuple(int(c*a//100) for c in LIME))

    # Big Q mark — flips/scales in
    t1 = clamp(t/0.25)
    scale_q = elastic_out(t1)
    sz_q = int(280 * scale_q)
    if sz_q > 20:
        fq = font(sz_q)
        draw_shadow(draw, "?", W//2, 420, fq, LIME, 10)

    # "नेपालमा" slides from left
    if t > 0.2:
        t2 = clamp((t-0.2)/0.2)
        x2 = lerp(-300, W//2, ease_out(t2))
        f2 = nfont(95)
        draw_shadow(draw, "नेपालमा", int(x2), 780, f2, WHITE, 6)

    # "सरकार" — big, lime, slams
    if t > 0.38:
        t3 = clamp((t-0.38)/0.18)
        s3 = lerp(2.0, 1.0, ease_out(t3))
        sz3 = int(130 * s3)
        f3 = nfont(sz3)
        draw_shadow(draw, "सरकार", W//2, 970, f3, LIME, 8)

    # "कसरी बन्छ?" slides from right
    if t > 0.55:
        t4 = clamp((t-0.55)/0.2)
        x4 = lerp(W+200, W//2, ease_out(t4))
        f4 = nfont(90)
        draw_shadow(draw, "कसरी बन्छ?", int(x4), 1130, f4, ORANGE, 6)

    # underline bar animates
    if t > 0.7:
        t5 = clamp((t-0.7)/0.25)
        bar_w = int(600 * ease_out(t5))
        draw.rectangle([W//2-bar_w//2, 1195, W//2+bar_w//2, 1203], fill=LIME)

    # "के थाहा छ तपाईंलाई?" — small, fades
    if t > 0.78:
        t6 = clamp((t-0.78)/0.2)
        a6 = int(255 * ease_out(t6))
        f6 = nfont(58)
        draw_text_c(draw, "के थाहा छ तपाईंलाई?", W//2, 1310, f6, (a6, a6, a6))

    return img

# ══════════════════════════════════════════════════════════════════════
# SCENE 2: STEP 1 — Election (4–10s)
# Ballot + 275 seats visual + arrow breakdown
# ══════════════════════════════════════════════════════════════════════
def scene_step1(img, t):
    draw = ImageDraw.Draw(img)

    # Step indicator row at top
    for i in range(5):
        active = (i == 0)
        cx = 140 + i * 200
        draw_step_circle(draw, cx, 120, 42, i+1, active)

    # Progress bar
    prog_w = int(800 * clamp(t/0.3))
    draw.rectangle([140, 175, 140+prog_w, 185], fill=LIME)
    draw.rectangle([140, 175, 940, 185], outline=GREY, width=2)

    # STEP label
    t0 = clamp(t/0.15)
    f_step = font(42)
    draw_text_c(draw, "STEP 1", 140, 240, f_step,
                tuple(int(c*ease_out(t0)) for c in LIME), anchor="lm")

    # Ballot box emoji / icon area
    if t > 0.1:
        t1 = clamp((t-0.1)/0.2)
        s1 = elastic_out(t1)
        sz_e = int(180 * s1)
        if sz_e > 10:
            fe = nfont(sz_e)
            draw_text_c(draw, "🗳️", W//2, 480, fe, WHITE)

    # "निर्वाचन" BIG
    if t > 0.25:
        t2 = clamp((t-0.25)/0.2)
        y2 = lerp(700, 640, ease_out(t2))
        f2 = nfont(120)
        draw_shadow(draw, "निर्वाचन", W//2, int(y2), f2, LIME, 8)

    # Two columns: FPTP vs PR
    if t > 0.42:
        t3 = clamp((t-0.42)/0.25)

        # Left card — FPTP
        card_x = lerp(-500, 80, ease_out(t3))
        draw_rounded_rect(draw, int(card_x), 780, int(card_x)+420, 1050,
                          20, fill=(25,45,25), outline=LIME, width=3)
        fa = nfont(72)
        fb = nfont(44)
        draw_text_c(draw, "१६५", int(card_x)+210, 870, fa, LIME)
        draw_text_c(draw, "प्रत्यक्ष", int(card_x)+210, 960, fb, WHITE)
        draw_text_c(draw, "(FPTP)", int(card_x)+210, 1020, font(36), GREY)

    if t > 0.55:
        t4 = clamp((t-0.55)/0.25)
        # Right card — PR
        card_x2 = lerp(W+100, 580, ease_out(t4))
        draw_rounded_rect(draw, int(card_x2), 780, int(card_x2)+420, 1050,
                          20, fill=(45,25,10), outline=ORANGE, width=3)
        fa2 = nfont(72)
        fb2 = nfont(44)
        draw_text_c(draw, "११०", int(card_x2)+210, 870, fa2, ORANGE)
        draw_text_c(draw, "समानुपातिक", int(card_x2)+210, 960, fb2, WHITE)
        draw_text_c(draw, "(PR)", int(card_x2)+210, 1020, font(36), GREY)

    # Total = 275 — big reveal
    if t > 0.7:
        t5 = clamp((t-0.7)/0.25)
        s5 = elastic_out(t5)

        # Plus sign
        draw_text_c(draw, "+", W//2, 920, nfont(80), WHITE)

        # = 275
        sz5 = int(110 * s5)
        f5 = nfont(sz5)
        draw_shadow(draw, "= २७५ सिट", W//2, 1150, f5, GOLD, 6)

    # Arrow pointing down
    if t > 0.85:
        t6 = clamp((t-0.85)/0.15)
        arr_len = int(80 * ease_out(t6))
        draw_arrow_down(draw, W//2, 1250, arr_len, LIME, width=8, head=28)

    # जनताले भोट दिन्छन्!
    if t > 0.88:
        t7 = clamp((t-0.88)/0.12)
        f7 = nfont(62)
        y7 = lerp(H, 1430, ease_out(t7))
        draw_text_c(draw, "जनताले भोट दिन्छन्!", W//2, int(y7), f7, WHITE)

    return img

# ══════════════════════════════════════════════════════════════════════
# SCENE 3: STEP 2 — Majority (10–16s)
# Before/After style: No majority vs Majority
# ══════════════════════════════════════════════════════════════════════
def scene_step2(img, t):
    draw = ImageDraw.Draw(img)

    # Step indicators
    for i in range(5):
        active = (i == 1)
        draw_step_circle(draw, 140 + i*200, 120, 42, i+1, active)

    f_step = font(42)
    draw_text_c(draw, "STEP 2", 140, 240, f_step, LIME, anchor="lm")

    # "बहुमत" big header
    t0 = clamp(t/0.2)
    f_title = nfont(115)
    draw_shadow(draw, "बहुमत", W//2, 380, f_title,
                tuple(int(c*ease_out(t0)) for c in LIME), 8)

    # Threshold number — big stamp
    if t > 0.18:
        t1 = clamp((t-0.18)/0.2)
        s1 = lerp(4.0, 1.0, ease_out(t1))
        sz1 = int(160 * s1)
        f1 = nfont(sz1)
        draw_shadow(draw, "१३८+", W//2, 620, f1, ORANGE, 10)
        f_sub = nfont(52)
        draw_text_c(draw, "सिट चाहिन्छ", W//2, 740, f_sub,
                    tuple(int(c*ease_out(t1)) for c in WHITE))

    # BEFORE / AFTER split — two cards
    if t > 0.38:
        t2 = clamp((t-0.38)/0.25)

        # LEFT — No majority (red tint)
        bx = lerp(-500, 40, ease_out(t2))
        draw_rounded_rect(draw, int(bx), 820, int(bx)+460, 1150,
                          20, fill=(50,10,10), outline=RED, width=3)
        draw_text_c(draw, "❌", int(bx)+230, 900, nfont(80), RED)
        f_card = nfont(50)
        draw_text_c(draw, "एकल बहुमत", int(bx)+230, 990, f_card, WHITE)
        draw_text_c(draw, "छैन", int(bx)+230, 1055, f_card, RED)
        f_sm = nfont(40)
        draw_text_c(draw, "→ गठबन्धन", int(bx)+230, 1120, f_sm, GREY)

    if t > 0.52:
        t3 = clamp((t-0.52)/0.25)

        # RIGHT — Majority (green tint)
        bx2 = lerp(W+100, 580, ease_out(t3))
        draw_rounded_rect(draw, int(bx2), 820, int(bx2)+460, 1150,
                          20, fill=(10,45,10), outline=GREEN, width=3)
        draw_text_c(draw, "✅", int(bx2)+230, 900, nfont(80), GREEN)
        f_card2 = nfont(50)
        draw_text_c(draw, "बहुमत", int(bx2)+230, 990, f_card2, WHITE)
        draw_text_c(draw, "पायो!", int(bx2)+230, 1055, f_card2, GREEN)
        f_sm2 = nfont(40)
        draw_text_c(draw, "→ सरकार बन्छ", int(bx2)+230, 1120, f_sm2, LIME)

    # VS badge in center
    if t > 0.6:
        t4 = clamp((t-0.6)/0.2)
        s4 = elastic_out(t4)
        sz4 = int(60 * s4)
        draw.ellipse([W//2-sz4, 960-sz4, W//2+sz4, 960+sz4],
                     fill=(40,40,40), outline=WHITE, width=3)
        draw_text_c(draw, "VS", W//2, 960, font(sz4-10), WHITE)

    # Arrow + conclusion
    if t > 0.78:
        t5 = clamp((t-0.78)/0.2)
        draw_arrow_down(draw, W//2, 1200, int(60*ease_out(t5)), LIME)

    if t > 0.82:
        t6 = clamp((t-0.82)/0.18)
        f6 = nfont(58)
        y6 = lerp(H, 1430, ease_out(t6))
        draw_text_c(draw, "बहुमत = सरकार!", W//2, int(y6), f6, LIME)

    return img

# ══════════════════════════════════════════════════════════════════════
# SCENE 4: STEP 3 — PM Appointment (16–21s)
# Step-by-step arrow flow: राष्ट्रपति → नेता → प्रधानमन्त्री
# ══════════════════════════════════════════════════════════════════════
def scene_step3(img, t):
    draw = ImageDraw.Draw(img)

    for i in range(5):
        active = (i == 2)
        draw_step_circle(draw, 140 + i*200, 120, 42, i+1, active)

    draw_text_c(draw, "STEP 3", 140, 240, font(42), LIME, anchor="lm")

    # Title
    t0 = clamp(t/0.15)
    f_t = nfont(100)
    draw_shadow(draw, "प्रधानमन्त्री", W//2, 380, f_t,
                tuple(int(c*ease_out(t0)) for c in LIME), 7)
    draw_text_c(draw, "नियुक्ति", W//2, 490, nfont(85),
                tuple(int(c*ease_out(t0)) for c in WHITE))

    # Flow diagram — 3 boxes with arrows
    boxes = [
        ("🏛️", "राष्ट्रपति", BLUE),
        ("👤", "दलको नेता", LIME),
        ("⭐", "प्रधानमन्त्री", GOLD),
    ]

    for i, (emoji, label, col) in enumerate(boxes):
        delay = 0.2 + i * 0.22
        if t > delay:
            ti = clamp((t-delay)/0.2)
            si = elastic_out(ti)
            cy = 750 + i * 310
            box_h = int(130 * si)
            if box_h > 10:
                draw_rounded_rect(draw,
                    W//2-240, cy-box_h//2, W//2+240, cy+box_h//2,
                    20, fill=(20,20,20),
                    outline=col, width=4)
                if ti > 0.5:
                    fe = nfont(int(70 * ti))
                    draw_text_c(draw, emoji, W//2-130, cy, fe, col)
                    fl = nfont(int(60 * ti))
                    draw_text_c(draw, label, W//2+50, cy, fl, WHITE)

            # Arrow between boxes
            if i < 2 and ti > 0.8:
                ta = clamp((ti-0.8)/0.2)
                arr_len = int(60 * ease_out(ta))
                draw_arrow_down(draw, W//2, cy+65, arr_len, col, width=6, head=22)

    # 30-day deadline note
    if t > 0.82:
        t_note = clamp((t-0.82)/0.18)
        draw_rounded_rect(draw, 80, 1650, W-80, 1800, 15,
                          fill=(40,20,0), outline=ORANGE, width=3)
        fn = nfont(50)
        a_n = int(255*ease_out(t_note))
        draw_text_c(draw, "⏱️ ३० दिनभित्र विश्वासको मत!", W//2, 1725,
                    fn, (a_n, int(107*a_n//255), 0))

    return img

# ══════════════════════════════════════════════════════════════════════
# SCENE 5: STEP 4+5 — Cabinet + Vote of Confidence (21–26s)
# ══════════════════════════════════════════════════════════════════════
def scene_step45(img, t):
    draw = ImageDraw.Draw(img)

    for i in range(5):
        active = (i in [3, 4])
        draw_step_circle(draw, 140 + i*200, 120, 42, i+1, active)

    draw_text_c(draw, "STEP 4 + 5", 140, 240, font(38), LIME, anchor="lm")

    # STEP 4 — Cabinet
    t0 = clamp(t/0.18)
    draw_shadow(draw, "मन्त्रिपरिषद्", W//2, 360, nfont(100),
                tuple(int(c*ease_out(t0)) for c in LIME), 7)

    # Cabinet grid — 25 seats shown as grid
    if t > 0.15:
        t1 = clamp((t-0.15)/0.4)
        cols, rows = 5, 5
        cx_start = 160
        cy_start = 480
        gap_x = 155
        gap_y = 120
        filled = int(25 * ease_out(t1))

        for i in range(25):
            col_i = i % cols
            row_i = i // cols
            cx = cx_start + col_i * gap_x
            cy = cy_start + row_i * gap_y
            is_filled = i < filled
            fill_col = LIME if is_filled else (30, 30, 30)
            draw.ellipse([cx-35, cy-35, cx+35, cy+35],
                         fill=fill_col,
                         outline=GREY if not is_filled else BLACK, width=2)
            if is_filled:
                draw_text_c(draw, "👤", cx, cy, nfont(42), BLACK)

        # Max label
        if t1 > 0.7:
            ta = ease_out(clamp((t1-0.7)/0.3))
            draw_rounded_rect(draw, 250, 1110, 830, 1195, 15,
                              fill=(30,45,10), outline=LIME, width=3)
            draw_text_c(draw, "अधिकतम २५ मन्त्री", W//2, 1155, nfont(55),
                        tuple(int(c*ta) for c in LIME))

    # Divider
    if t > 0.58:
        tdiv = clamp((t-0.58)/0.15)
        dw = int(800 * ease_out(tdiv))
        draw.rectangle([W//2-dw//2, 1230, W//2+dw//2, 1236], fill=GREY)

    # STEP 5 — Vote of confidence
    if t > 0.62:
        t2 = clamp((t-0.62)/0.18)
        draw_shadow(draw, "विश्वासको मत", W//2, 1330, nfont(85),
                    tuple(int(c*ease_out(t2)) for c in ORANGE), 6)

    if t > 0.75:
        t3 = clamp((t-0.75)/0.2)
        # Two outcome cards
        bx = lerp(-400, 60, ease_out(t3))
        draw_rounded_rect(draw, int(bx), 1420, int(bx)+380, 1580,
                          15, fill=(10,40,10), outline=GREEN, width=3)
        draw_text_c(draw, "✅ पायो", int(bx)+190, 1500, nfont(62), GREEN)

    if t > 0.85:
        t4 = clamp((t-0.85)/0.15)
        bx2 = lerp(W+100, 640, ease_out(t4))
        draw_rounded_rect(draw, int(bx2), 1420, int(bx2)+380, 1580,
                          15, fill=(40,10,10), outline=RED, width=3)
        draw_text_c(draw, "❌ सरकार खारेज", int(bx2)+190, 1500, nfont(52), RED)

    return img

# ══════════════════════════════════════════════════════════════════════
# SCENE 6: SUMMARY — Flashcard style (26–30s)
# Full flow recap + CTA
# ══════════════════════════════════════════════════════════════════════
def scene_summary(img, t):
    draw = ImageDraw.Draw(img)

    # Glowing background pulse
    pulse = 0.5 + 0.5*math.sin(t*math.pi*4)
    for r_size in range(350, 100, -60):
        a = int(4 * pulse)
        draw.ellipse([W//2-r_size, 960-r_size, W//2+r_size, 960+r_size],
                     fill=(a, int(a*1.3), 0))

    # "सरल भाषामा:" header
    t0 = clamp(t/0.15)
    draw_shadow(draw, "सरल भाषामा:", W//2, 220, nfont(70),
                tuple(int(c*ease_out(t0)) for c in GOLD), 5)

    # Flow steps — appear one by one
    steps = [
        ("🗳️ भोट", LIME),
        ("↓", WHITE),
        ("🏆 सिट जित्छ", LIME),
        ("↓", WHITE),
        ("👤 प्रधानमन्त्री", ORANGE),
        ("↓", WHITE),
        ("🏛️ मन्त्रिपरिषद्", LIME),
        ("↓", WHITE),
        ("✅ सरकार!", GOLD),
    ]

    for i, (text, col) in enumerate(steps):
        delay = 0.12 + i * 0.07
        if t > delay:
            ti = clamp((t-delay)/0.1)
            is_arrow = text == "↓"
            cy = 370 + i * 110
            if is_arrow:
                if ti > 0.5:
                    draw_arrow_down(draw, W//2, cy-20, 40, GREY, width=5, head=18)
            else:
                a_c = int(255*ease_out(ti))
                sz = 62 if "सरकार!" not in text else 80
                fn = nfont(sz)
                x_off = lerp(-60, 0, ease_out(ti))
                draw_text_c(draw, text, W//2 + int(x_off), cy, fn,
                            tuple(min(255, int(c*(a_c/255))) for c in col))

    # CTA card
    if t > 0.75:
        t_cta = clamp((t-0.75)/0.22)
        card_y = lerp(H+100, 1560, ease_out(t_cta))

        draw_rounded_rect(draw, 60, int(card_y)-90, W-60, int(card_y)+90,
                          25, fill=(25,45,10), outline=LIME, width=4)

        f_cta = nfont(58)
        draw_text_c(draw, "Follow गर्नुस् — Nepal Cyber Firm 🇳🇵",
                    W//2, int(card_y)-20, f_cta, WHITE)
        f_cta2 = nfont(48)
        draw_text_c(draw, "Comment: YES / NO 👇",
                    W//2, int(card_y)+45, f_cta2, LIME)

    # Pulse border on last frames
    if t > 0.88:
        t_b = clamp((t-0.88)/0.12)
        bw = int(6 * pulse)
        draw.rectangle([bw, bw, W-bw, H-bw], outline=LIME, width=bw)

    return img

# ══════════════════════════════════════════════════════════════════════
# SCENE TABLE
# ══════════════════════════════════════════════════════════════════════
SCENES = [
    (0.0,  4.0,  scene_hook),
    (4.0,  10.0, scene_step1),
    (10.0, 16.0, scene_step2),
    (16.0, 21.0, scene_step3),
    (21.0, 26.0, scene_step45),
    (26.0, 30.0, scene_summary),
]
TOTAL = 30.0

# ── flash transition ────────────────────────────────────────────────
def flash(t_local, dur=0.05):
    if t_local < dur:
        return 1.0 - t_local/dur
    return 0.0

# ── MAIN RENDER ─────────────────────────────────────────────────────
total_frames = int(TOTAL * FPS)
print(f"🎬 Rendering {total_frames} frames ({TOTAL}s @ {FPS}fps)...")

for fi in range(total_frames):
    ts = fi / FPS
    img = new_frame(DARK)

    for (s, e, fn) in SCENES:
        if s <= ts < e:
            tl = clamp((ts - s) / (e - s))
            try:
                img = fn(img, tl)
            except Exception as ex:
                pass

            # Flash transitions
            fl = flash(tl)
            if fl > 0.01:
                overlay = Image.new("RGB", (W, H), (255,255,255))
                img = Image.blend(img, overlay, fl * 0.65)

            end_fl = flash(1.0 - tl, 0.04)
            if end_fl > 0.01:
                overlay = Image.new("RGB", (W, H), BLACK)
                img = Image.blend(img, overlay, end_fl)
            break

    img.save(f"{FRAMES_DIR}/frame_{fi:05d}.png", optimize=False)

    if fi % 90 == 0:
        print(f"  {fi/total_frames*100:.0f}% — {fi}/{total_frames}")

print("✅ All frames done!")
