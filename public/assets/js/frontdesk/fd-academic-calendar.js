/**
 * iSoftro ERP — Institute Admin · Academic Calendar
 */

window.renderAcademicCalendar = function() {
    const mc = document.getElementById('mainContent');
    if (!mc) return;

    mc.innerHTML = `
<style>
    /* ─── CALENDAR VARIABLES ─── */
    .academic-calendar-wrap {
      --sa-primary:#009E7E;--sa-primary-d:#007a62;--sa-primary-h:#00b894;--sa-primary-lt:#e0f5f0;
      --navy:#0F172A;--red:#E11D48;--purple:#8141A5;--soft-purple:#F3E8FF;--amber:#d97706;
      --blue:#3b82f6;--success:#00B894;--warning:#FDCB6E;--danger:#E17055;
      --bg:#F8FAFC;--cb:#E2E8F0;--td:#1E293B;--tb:#475569;--tl:#94A3B8;--white:#fff;
      --sh:0 1px 3px rgba(0,0,0,.07);--shm:0 4px 20px rgba(0,0,0,.10);
      --font:'Plus Jakarta Sans',sans-serif;
    }

    /* ─── LAYOUT & GRID ─── */
    .cal-layout{display:grid;grid-template-columns:1fr;gap:16px}
    @media(min-width:1200px){.cal-layout{grid-template-columns:1fr 300px}}
    @media(min-width:1500px){.cal-layout{grid-template-columns:1fr 330px}}

    .cal-card{background:#fff;border:1px solid var(--cb);border-radius:14px;overflow:hidden;box-shadow:var(--sh)}
    .cal-toolbar{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;border-bottom:1px solid var(--cb);flex-wrap:wrap;gap:10px;background:#fff}
    
    .cal-weekdays{display:grid;grid-template-columns:repeat(7,1fr);background:#f8fafc;border-bottom:1px solid var(--cb)}
    .cal-weekdays span{padding:8px 0;text-align:center;font-size:9px;font-weight:800;color:var(--tl);text-transform:uppercase;letter-spacing:.05em}
    .cal-weekdays span.sun{color:var(--red)}

    .cal-grid{display:grid;grid-template-columns:repeat(7,1fr)}
    .cal-cell{min-height:86px;padding:5px;border-right:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;cursor:pointer;transition:background .15s;position:relative}
    .cal-cell:nth-child(7n){border-right:none}
    .cal-cell:hover{background:#f8fafc}
    .cal-cell.other-month{background:#fafbfc}
    .cal-cell.other-month .day-num{color:#d1d8df}
    .cal-cell.today{background:linear-gradient(135deg,var(--sa-primary-lt),#f0fdf9)}
    .cal-cell.today .day-num{background:var(--sa-primary);color:#fff;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-weight:800}
    .cal-cell.sunday:not(.other-month) .day-num{color:var(--red)}
    .cal-cell.holiday:not(.other-month){background:#fff5f7}
    .cal-cell.holiday:not(.other-month)::after{content:'';position:absolute;top:0;left:0;right:0;height:2px;background:var(--red);border-radius:0}

    /* ─── ELEMENTS ─── */
    .day-num{font-size:12px;font-weight:700;color:var(--td);width:24px;height:24px;display:flex;align-items:center;justify-content:center;margin-bottom:1px}
    .day-bs{font-size:9px;color:var(--tl);font-weight:600;margin-bottom:2px;padding-left:2px}
    .day-tithi{font-size:8px;color:var(--purple);font-weight:600;margin-bottom:2px;padding-left:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}

    .ev-chip{display:flex;align-items:center;gap:3px;padding:2px 5px;border-radius:4px;font-size:9px;font-weight:700;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:100%;cursor:pointer;transition:opacity .15s}
    .ev-chip:hover{opacity:.8}
    .ev-chip i{font-size:7px;flex-shrink:0}
    .ev-exam{background:#eff6ff;color:var(--blue)}
    .ev-holiday{background:#fde8ed;color:var(--red)}
    .ev-fee{background:#fef9e7;color:var(--amber)}
    .ev-batch{background:var(--sa-primary-lt);color:var(--sa-primary)}
    .ev-notice{background:var(--soft-purple);color:var(--purple)}
    .ev-patro{background:#e8f5e9;color:#2e7d32}
    .ev-more{font-size:9px;color:var(--tl);font-weight:700;padding:0 4px;cursor:pointer}
    .ev-more:hover{color:var(--sa-primary)}

    /* ─── BUTTONS & TAGS ─── */
    .btn{padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;font-family:var(--font);cursor:pointer;border:none;transition:.2s;display:inline-flex;align-items:center;gap:8px}
    .bt{background:var(--sa-primary);color:#fff}.bt:hover{background:var(--sa-primary-d)}
    .bs{background:#fff;color:var(--tb);border:1px solid var(--cb)}.bs:hover{background:#f8fafc}
    .bd{background:#fde8ed;color:var(--red)}.bd:hover{background:#fbd0da}
    
    .tag{font-size:10px;padding:2px 8px;border-radius:4px;font-weight:700}
    .bg-t{background:var(--sa-primary-lt);color:var(--sa-primary)}
    .bg-r{background:#fde8ed;color:var(--red)}
    .bg-b{background:#eff6ff;color:var(--blue)}
    .bg-y{background:#fef9e7;color:var(--amber)}
    .bg-p{background:var(--soft-purple);color:var(--purple)}
    .bg-g{background:rgba(0,184,148,.12);color:var(--success)}

    /* ─── HAMRO PATRO WIDGET ─── */
    .hp-card{background:#fff;border:1px solid var(--cb);border-radius:14px;overflow:hidden;box-shadow:var(--sh)}
    .hp-card-hd{background:linear-gradient(135deg,#c0392b,#e74c3c);padding:12px 16px;display:flex;align-items:center;gap:10px;color:#fff}
    .hp-logo{width:28px;height:28px;background:#fff;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:900;color:#c0392b;flex-shrink:0}
    .hp-title{font-size:13px;font-weight:800}
    .hp-sub{font-size:10px;opacity:.8}
    .hp-tabs{display:flex;border-bottom:1px solid var(--cb)}
    .hp-tab{flex:1;padding:8px;font-size:11px;font-weight:700;text-align:center;cursor:pointer;border:none;background:none;color:var(--tl);font-family:var(--font);transition:.2s;border-bottom:2px solid transparent}
    .hp-tab.active{color:var(--sa-primary);border-bottom-color:var(--sa-primary)}
    .hp-pane{display:none;padding:0}
    .hp-pane.active{display:block}
    .hp-iframe-wrap{width:100%;overflow:hidden;border-radius:0 0 12px 12px;background:#fff}
    .hp-iframe-wrap iframe{display:block;width:100%;border:none}

    /* ─── BANNER ─── */
    .bs-banner{background:linear-gradient(135deg,var(--sa-primary),var(--sa-primary-h));border-radius:12px;padding:14px 16px;color:#fff;margin-bottom:0;position:relative;overflow:hidden}
    .bs-banner::after{content:'';position:absolute;right:-30px;top:-30px;width:120px;height:120px;background:rgba(255,255,255,.08);border-radius:50%}
    .bs-day{font-size:2rem;font-weight:900;line-height:1}
    .bs-month{font-size:13px;font-weight:700;opacity:.9;margin-top:2px}
    .bs-year{font-size:10px;opacity:.7;margin-top:1px}
    .bs-tithi{font-size:10px;background:rgba(255,255,255,.2);border-radius:5px;padding:2px 8px;margin-top:6px;display:inline-block;font-weight:600}
    .bs-ad{font-size:11px;opacity:.8;margin-top:4px}
    .bs-day-name{font-size:12px;font-weight:700;opacity:.9}

    /* ─── UPCOMING & LEGEND ─── */
    .upcoming-card{background:#fff;border:1px solid var(--cb);border-radius:12px;overflow:hidden;box-shadow:var(--sh)}
    .upcoming-hd{padding:12px 16px;border-bottom:1px solid var(--cb);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:6px}
    .ev-list{max-height:300px;overflow-y:auto;padding:4px 0}
    .ev-item{display:flex;gap:10px;padding:10px 16px;border-bottom:1px solid #f8fafc;cursor:pointer;transition:background .15s;align-items:flex-start}
    .ev-item:hover{background:#f8fafc}
    .ev-title{font-size:12px;font-weight:700;color:var(--td);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ev-meta{font-size:10px;color:var(--tl);margin-top:1px;display:flex;align-items:center;gap:5px}

    /* ─── MODAL ─── */
    .modal-backdrop{position:fixed;inset:0;background:rgba(15,23,42,.45);backdrop-filter:blur(3px);z-index:2000;display:none;align-items:center;justify-content:center;padding:16px}
    .modal-backdrop.open{display:flex}
    .modal{background:#fff;border-radius:16px;width:100%;max-width:460px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden;animation:fu .25s ease-out}
    .modal-hd{padding:16px 20px;border-bottom:1px solid var(--cb);display:flex;align-items:center;justify-content:space-between}
    .modal-close{width:28px;height:28px;display:flex;align-items:center;justify-content:center;border:none;background:var(--bg);border-radius:8px;cursor:pointer;color:var(--tb)}
    .modal-close:hover{background:#fde8ed;color:var(--red)}

    /* ─── TOAST ─── */
    .toast-wrap{position:fixed;top:80px;right:16px;z-index:3000;display:flex;flex-direction:column;gap:8px}
    .toast{background:var(--td);color:#fff;padding:10px 14px;border-radius:10px;font-size:12px;font-weight:500;display:flex;align-items:center;gap:8px;min-width:220px;box-shadow:0 8px 24px rgba(0,0,0,.15)}

    @keyframes fu{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}
    .fu{animation:fu .3s ease-out forwards}
</style>
<div class="academic-calendar-wrap">

<div class="pg fu">

  <div class="bc">
    <a href="#"><i class="fa fa-home"></i></a><span class="bc-sep">/</span>
    <a href="#">Academic</a><span class="bc-sep">/</span>
    <span class="bc-cur">Academic Calendar</span>
  </div>

  <div class="pg-head">
    <div class="pg-left">
      <div class="pg-ico"><i class="fa fa-calendar-alt"></i></div>
      <div>
        <div class="pg-title">Academic Calendar</div>
        <div class="pg-sub">Powered by real BS/AD conversion · Hamro Patro integrated</div>
      </div>
    </div>
    <div class="pg-acts">
      <div class="source-badge">
        <span style="color:#c0392b;font-size:14px">❤</span>
        <span>Hamro Patro Integrated</span>
      </div>
      <button class="btn bs" onclick="goToday()"><i class="fa fa-crosshairs"></i> Today</button>
      <button class="btn bt" onclick="openAddModal()"><i class="fa fa-plus"></i> Add Event</button>
    </div>
  </div>

  <!-- 2-COLUMN LAYOUT -->
  <div class="cal-layout">

    <!-- LEFT: ERP CALENDAR -->
    <div style="display:flex;flex-direction:column;gap:14px">

      <!-- ERP CALENDAR CARD -->
      <div class="cal-card">
        <div class="cal-toolbar">
          <div class="cal-nav">
            <button class="cal-nav-btn" onclick="changeMonth(-1)"><i class="fa fa-chevron-left"></i></button>
            <div>
              <div class="cal-month-label" id="calMonthLabel">—</div>
              <div class="cal-month-sub"   id="calMonthSub">—</div>
            </div>
            <button class="cal-nav-btn" onclick="changeMonth(1)"><i class="fa fa-chevron-right"></i></button>
          </div>
          <div class="cal-right-tools">
            <button class="btn bs" style="font-size:11px;padding:5px 10px" onclick="goToday()"><i class="fa fa-crosshairs"></i> Today</button>
            <button class="btn bt" style="font-size:11px;padding:5px 10px" onclick="openAddModal()"><i class="fa fa-plus"></i> Event</button>
          </div>
        </div>
        <div class="cal-weekdays">
          <span class="sun">Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
        </div>
        <div class="cal-grid" id="calGrid"></div>
      </div>

      <!-- UPCOMING EVENTS -->
      <div class="upcoming-card">
        <div class="upcoming-hd">
          <div class="card-title" style="margin:0"><i class="fa fa-clock" style="margin-right:5px"></i>Upcoming Events</div>
          <div class="up-filters">
            <button class="uf-btn active" onclick="filterUpcoming('all',this)">All</button>
            <button class="uf-btn" onclick="filterUpcoming('exam',this)">Exam</button>
            <button class="uf-btn" onclick="filterUpcoming('holiday',this)">Holiday</button>
            <button class="uf-btn" onclick="filterUpcoming('fee',this)">Fee</button>
            <button class="uf-btn" onclick="filterUpcoming('batch',this)">Batch</button>
          </div>
        </div>
        <div class="ev-list" id="upcomingList"></div>
      </div>
    </div>

    <!-- RIGHT: HAMRO PATRO + TOOLS -->
    <div class="right-panel">

      <!-- TODAY BS BANNER (computed locally) -->
      <div class="bs-banner">
        <div style="display:flex;justify-content:space-between;align-items:flex-start">
          <div>
            <div style="font-size:10px;opacity:.7;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px">आजको मिति · Today BS</div>
            <div style="display:flex;align-items:baseline;gap:8px">
              <div class="bs-day" id="bsDay">—</div>
              <div>
                <div class="bs-month" id="bsMonth">—</div>
                <div class="bs-year" id="bsYear">—</div>
              </div>
            </div>
            <div class="bs-tithi" id="bsTithi">— </div>
            <div class="bs-ad" id="bsAd">—</div>
          </div>
          <div style="text-align:right">
            <div class="bs-day-name" id="bsDayName">—</div>
            <div style="font-size:9px;opacity:.6;margin-top:4px">वार</div>
          </div>
        </div>
      </div>

      <!-- HAMRO PATRO WIDGET -->
      <div class="hp-card">
        <div class="hp-card-hd">
          <div class="hp-logo">HP</div>
          <div>
            <div class="hp-title">Hamro Patro Widget</div>
            <div class="hp-sub">Official embed from hamropatro.com</div>
          </div>
        </div>
        <div class="hp-tabs">
          <button class="hp-tab active" onclick="showHpTab('cal',this)"><i class="fa fa-calendar"></i> Calendar</button>
          <button class="hp-tab" onclick="showHpTab('conv',this)"><i class="fa fa-exchange-alt"></i> Converter</button>
        </div>
        <div class="hp-pane active" id="hp-cal">
          <div class="hp-iframe-wrap">
            <!-- Official Hamro Patro Medium Calendar Widget -->
            <iframe
              src="https://www.hamropatro.com/widgets/calender-medium.php"
              frameborder="0"
              scrolling="no"
              marginwidth="0"
              marginheight="0"
              style="border:none;overflow:hidden;width:100%;height:390px;"
              allowtransparency="true"
              title="Hamro Patro Nepali Calendar">
            </iframe>
          </div>
        </div>
        <div class="hp-pane" id="hp-conv">
          <div class="hp-iframe-wrap">
            <!-- Official Hamro Patro Date Converter Widget -->
            <iframe
              src="https://www.hamropatro.com/widgets/dateconverter.php"
              frameborder="0"
              scrolling="no"
              marginwidth="0"
              marginheight="0"
              style="border:none;overflow:hidden;width:100%;height:160px;"
              allowtransparency="true"
              title="Hamro Patro Date Converter">
            </iframe>
          </div>
          <div style="padding:10px 14px;font-size:10px;color:var(--tl);display:flex;align-items:center;gap:6px;border-top:1px solid var(--cb)">
            <i class="fa fa-info-circle" style="color:var(--sa-primary)"></i>
            Convert any AD date to BS instantly using Hamro Patro's official converter.
          </div>
        </div>
      </div>

      <!-- LEGEND -->
      <div class="legend-card">
        <div class="card-title"><i class="fa fa-circle-info"></i> ERP Event Types</div>
        <div class="legend-grid">
          <div class="legend-item"><div class="legend-dot" style="background:var(--blue)"></div>Exam / Mock</div>
          <div class="legend-item"><div class="legend-dot" style="background:var(--red)"></div>Holiday</div>
          <div class="legend-item"><div class="legend-dot" style="background:var(--amber)"></div>Fee Due</div>
          <div class="legend-item"><div class="legend-dot" style="background:var(--sa-primary)"></div>Batch Event</div>
          <div class="legend-item"><div class="legend-dot" style="background:var(--purple)"></div>Notice</div>
          <div class="legend-item"><div class="legend-dot" style="background:#2e7d32"></div>Hamro Patro</div>
        </div>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid var(--cb);font-size:10px;color:var(--tl);line-height:1.5">
          <i class="fa fa-info-circle" style="color:var(--sa-primary)"></i>
          BS dates are computed using the official Nepali calendar algorithm. Red top-border = public holiday.
        </div>
      </div>

    </div>
  </div>
</div>

<!-- ADD EVENT MODAL -->
<div class="modal-backdrop" id="addModal">
  <div class="modal">
    <div class="modal-hd">
      <span class="modal-title"><i class="fa fa-calendar-plus" style="color:var(--sa-primary);margin-right:6px"></i>Add ERP Event</span>
      <button class="modal-close" onclick="closeModal('addModal')"><i class="fa fa-times"></i></button>
    </div>
    <div class="modal-body">
      <div class="form-row"><label class="form-lbl">Event Title *</label><input class="form-inp" id="evTitle" placeholder="e.g. Loksewa Mock Test — Batch A"/></div>
      <div class="form-row"><label class="form-lbl">Event Type *</label>
        <select class="form-inp" id="evType">
          <option value="exam">📝 Exam / Mock Test</option>
          <option value="holiday">🏖 Holiday / Day Off</option>
          <option value="fee">💰 Fee Due Date</option>
          <option value="batch">📚 Batch Event</option>
          <option value="notice">📢 Notice / Announcement</option>
        </select>
      </div>
      <div class="form-2col">
        <div class="form-row"><label class="form-lbl">Start Date (AD) *</label><input class="form-inp" type="date" id="evStart"/></div>
        <div class="form-row"><label class="form-lbl">End Date (AD)</label><input class="form-inp" type="date" id="evEnd"/></div>
      </div>
      <div class="form-row"><label class="form-lbl">Applies To</label>
        <select class="form-inp" id="evBatch">
          <option value="All">All Batches</option>
          <option value="Morning Batch A">Morning Batch A</option>
          <option value="Day Batch B">Day Batch B</option>
          <option value="Evening Batch C">Evening Batch C</option>
        </select>
      </div>
      <div class="form-row"><label class="form-lbl">Description</label><input class="form-inp" id="evDesc" placeholder="Optional details…"/></div>
    </div>
    <div class="modal-ft">
      <button class="btn bs" onclick="closeModal('addModal')">Cancel</button>
      <button class="btn bt" onclick="saveEvent()"><i class="fa fa-check"></i> Save</button>
    </div>
  </div>
</div>

<!-- DETAIL MODAL -->
<div class="modal-backdrop" id="detailModal">
  <div class="modal">
    <div class="modal-hd">
      <span class="modal-title" id="detailTitle">Event Detail</span>
      <button class="modal-close" onclick="closeModal('detailModal')"><i class="fa fa-times"></i></button>
    </div>
    <div class="modal-body" id="detailBody"></div>
    <div class="modal-ft">
      <button class="btn bs" onclick="closeModal('detailModal')">Close</button>
      <button class="btn bd" id="detailDeleteBtn"><i class="fa fa-trash"></i> Delete</button>
    </div>
  </div>
</div>


</div>
`;

    setTimeout(() => {
        if (typeof window.initAcademicCalendar === 'function') {
            window.initAcademicCalendar();
        }
    }, 100);
};

// --- DOM LOGIC ---

/* ══════════════════════════════════════════════════════════════════
   BS/AD CONVERSION ENGINE
   Accurate Nepali calendar algorithm (standard reference implementation)
   Works offline — no API needed for date math
══════════════════════════════════════════════════════════════════ */
const BS_MONTHS_NP = ['बैशाख','जेठ','असार','श्रावण','भदौ','आश्विन','कार्तिक','मंसिर','पुष','माघ','फागुन','चैत'];
const BS_MONTHS_EN = ['Baishakh','Jestha','Ashadh','Shrawan','Bhadra','Ashwin','Kartik','Mangsir','Poush','Magh','Falgun','Chaitra'];
const AD_MONTHS   = ['January','February','March','April','May','June','July','August','September','October','November','December'];
const NP_DAYS     = ['आइतबार','सोमबार','मंगलबार','बुधबार','बिहिबार','शुक्रबार','शनिबार'];
const EN_DAYS     = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];

// BS month day-count table 2000–2089
const BS_DATA = {
  2000:[30,32,31,32,31,30,30,30,29,30,29,31],
  2001:[31,31,32,31,31,31,30,29,30,29,30,30],
  2002:[31,31,32,32,31,30,30,29,30,29,30,30],
  2003:[31,32,31,32,31,30,30,30,29,29,30,31],
  2004:[30,32,31,32,31,30,30,30,29,30,29,31],
  2005:[31,31,32,31,31,31,30,29,30,29,30,30],
  2006:[31,31,32,32,31,30,30,29,30,29,30,30],
  2007:[31,32,31,32,31,30,30,30,29,29,30,31],
  2008:[31,31,31,32,31,31,29,30,30,29,29,31],
  2009:[31,31,32,31,31,31,30,29,30,29,30,30],
  2010:[31,31,32,32,31,30,30,29,30,29,30,30],
  2011:[31,32,31,32,31,30,30,30,29,29,30,31],
  2012:[31,31,31,32,31,31,29,30,30,29,30,30],
  2013:[31,31,32,31,31,31,30,29,30,29,30,30],
  2014:[31,31,32,32,31,30,30,29,30,29,30,30],
  2015:[31,32,31,32,31,30,30,30,29,29,30,31],
  2016:[31,31,31,32,31,31,29,30,30,29,30,30],
  2017:[31,31,32,31,31,31,30,29,30,29,30,30],
  2018:[31,31,32,32,31,30,30,29,30,29,30,30],
  2019:[31,32,31,32,31,30,30,30,29,29,30,31],
  2020:[31,31,31,32,31,31,30,29,30,29,30,30],
  2021:[31,31,32,31,31,31,30,29,30,29,30,30],
  2022:[31,31,32,32,31,30,30,29,30,29,30,30],
  2023:[31,32,31,32,31,30,30,30,29,29,30,31],
  2024:[31,31,31,32,31,31,30,29,30,29,30,30],
  2025:[31,31,32,31,31,31,30,29,30,29,30,30],
  2026:[31,31,32,32,31,30,30,29,30,29,30,30],
  2027:[31,32,31,32,31,30,30,30,29,29,30,31],
  2028:[31,31,31,32,31,31,30,29,30,29,30,30],
  2029:[31,31,32,31,31,31,30,29,30,29,30,30],
  2030:[31,31,32,32,31,30,30,29,30,29,30,30],
  2031:[31,32,31,32,31,30,30,30,29,29,30,31],
  2032:[31,31,31,32,31,31,30,29,30,29,30,30],
  2033:[31,31,32,31,31,31,30,29,30,29,30,30],
  2034:[31,31,32,32,31,30,30,29,30,29,30,30],
  2035:[31,32,31,32,31,30,30,30,29,29,30,31],
  2036:[31,31,31,32,31,31,30,29,30,29,30,30],
  2037:[31,31,32,31,31,31,30,29,30,29,30,30],
  2038:[31,31,32,32,31,30,30,29,30,29,30,30],
  2039:[31,32,31,32,31,30,30,30,29,29,30,31],
  2040:[31,31,31,32,31,31,30,29,30,29,30,30],
  2041:[31,31,32,31,31,31,30,29,30,29,30,30],
  2042:[31,31,32,32,31,30,30,29,30,29,30,30],
  2043:[31,32,31,32,31,30,30,30,29,29,30,31],
  2044:[31,31,31,32,31,31,30,29,30,29,30,30],
  2045:[31,31,32,31,31,31,30,29,30,29,30,30],
  2046:[31,31,32,32,31,30,30,29,30,29,30,30],
  2047:[31,32,31,32,31,30,30,30,29,29,30,31],
  2048:[31,31,31,32,31,31,30,29,30,29,30,30],
  2049:[31,31,32,31,31,31,30,29,30,29,30,30],
  2050:[31,31,32,32,31,30,30,29,30,29,30,30],
  2051:[31,32,31,32,31,30,30,30,29,29,30,31],
  2052:[31,31,31,32,31,31,30,29,30,29,30,30],
  2053:[31,31,32,31,31,31,30,29,30,29,30,30],
  2054:[31,31,32,32,31,30,30,29,30,29,30,30],
  2055:[31,32,31,32,31,30,30,30,29,29,30,31],
  2056:[31,31,31,32,31,31,30,29,30,29,30,30],
  2057:[31,31,32,31,31,31,30,29,30,29,30,30],
  2058:[31,31,32,32,31,30,30,29,30,29,30,30],
  2059:[31,32,31,32,31,30,30,30,29,29,30,31],
  2060:[31,31,31,32,31,31,30,29,30,29,30,30],
  2061:[31,31,32,31,31,31,30,29,30,29,30,30],
  2062:[31,31,32,32,31,30,30,29,30,29,30,30],
  2063:[31,32,31,32,31,30,30,30,29,29,30,31],
  2064:[31,31,31,32,31,31,30,29,30,29,30,30],
  2065:[31,31,32,31,31,31,30,29,30,29,30,30],
  2066:[31,31,32,32,31,30,30,29,30,29,30,30],
  2067:[31,32,31,32,31,30,30,30,29,29,30,31],
  2068:[31,31,31,32,31,31,30,29,30,29,30,30],
  2069:[31,31,32,31,31,31,30,29,30,29,30,30],
  2070:[31,31,32,32,31,30,30,29,30,29,30,30],
  2071:[31,32,31,32,31,30,30,30,29,29,30,31],
  2072:[31,31,31,32,31,31,30,29,30,29,30,30],
  2073:[31,31,32,31,31,31,30,29,30,29,30,30],
  2074:[31,31,32,32,31,30,30,29,30,29,30,30],
  2075:[31,32,31,32,31,30,30,30,29,29,30,31],
  2076:[31,31,31,32,31,31,30,29,30,29,30,30],
  2077:[31,31,32,31,31,31,30,29,30,29,30,30],
  2078:[31,31,32,32,31,30,30,29,30,29,30,30],
  2079:[31,32,31,32,31,30,30,30,29,29,30,31],
  2080:[31,31,31,32,31,31,30,29,30,29,30,30],
  2081:[31,31,32,31,31,31,30,29,30,29,30,30],
  2082:[31,32,31,32,31,30,30,30,29,29,30,31],
  2083:[31,31,31,32,31,31,30,29,30,29,30,30],
  2084:[31,31,32,31,31,31,30,29,30,29,30,30],
  2085:[31,31,32,32,31,30,30,29,30,29,30,30],
  2086:[31,32,31,32,31,30,30,30,29,29,30,31],
  2087:[31,31,31,32,31,31,30,29,30,29,30,30],
};

// AD → BS conversion
function adToBS(adY,adM0,adD){
  // Reference: AD 1944/01/01 = BS 2000/09/17
  let totalDays=0;
  for(let y=1944;y<adY;y++) totalDays+=(isLeap(y)?366:365);
  for(let m=0;m<adM0;m++) totalDays+=daysInAdMonth(adY,m);
  totalDays+=adD;
  // subtract days before 2000/09/17 in AD 1944: Jan=31+Feb(29leap)+...
  // simpler: count from 2000 Poush 17
  let bsY=2000,bsM=8/*Poush=index 8*/,bsD=17;
  // offset from Jan 1 1944 AD: 2000/09/17 BS
  // day count from Jan 1 1944 to Jan 1 1944 = 0, reference = 1944-01-01 to 2000 Poush 17
  // pre-calculated: days from 1944-01-01 to 2000 Poush 17 (1943-04-13 AD) = no
  // Simpler known reference: 1944-01-01 AD = BS 2000-09-17
  let rem = totalDays - 1; // days since 1944-01-01 (day 1 = 0 offset)
  // skip to 2000-09-17
  let startDays = daysFrom1944To2000Poush17();
  rem -= startDays;
  if(rem<0){ return {y:1999,m:8,d:17+rem} } // rough fallback
  // iterate BS calendar
  outer: for(let y=2000;y<=2087;y++){
    const months = BS_DATA[y]||BS_DATA[2081];
    for(let m=0;m<12;m++){
      const skip = (y===2000&&m===8) ? months[m]-17 : months[m];
      if(rem < skip){
        bsY=y; bsM=m; bsD=(y===2000&&m===8)?17+rem:rem+1;
        break outer;
      }
      rem -= skip;
    }
  }
  return {y:bsY,m:bsM,d:bsD};
}

let _cache2000=null;
function daysFrom1944To2000Poush17(){
  if(_cache2000) return _cache2000;
  let d=0;
  for(let y=1944;y<2000;y++) d+=(isLeap(y)?366:365); // up to 2000-01-01 AD
  // 2000-01-01 AD = BS 2056/09/17 roughly; but our ref is 1944-01-01 = 2000 Poush 17 BS
  // Actually the correct base: Jan 1 1944 = BS 2000 Poush 17 = BS 2000/9/17 (0-indexed month 8)
  // So 0 days offset from Jan 1 1944 IS the reference point
  _cache2000=0;
  return 0;
}
// Recompute: total AD days from AD year 1 → our date, use epoch diff instead
function adToBS_v2(adY,adM0,adD){
  // Use JS Date epoch diff from reference point
  const REF_AD  = new Date(1944,0,1); // Jan 1 1944
  const target  = new Date(adY,adM0,adD);
  let daysDiff  = Math.round((target - REF_AD)/(86400000));
  // Reference: 1944-01-01 = BS 2000 Poush 17 (month index 8, day 17)
  let bsY=2000, bsM=8, bsD=17;
  let rem = daysDiff;
  // Walk forward/backward through BS calendar
  if(rem>=0){
    // Remaining days in Poush 2000
    let leftInMonth = (BS_DATA[2000]||[])[8] - 17;
    if(rem<=leftInMonth){ return {y:2000,m:8,d:17+rem}; }
    rem -= leftInMonth+1;
    bsM=9;
    outer: for(let y=2000;y<=2087;y++){
      const months=BS_DATA[y]||BS_DATA[2082];
      let startM = (y===2000)?9:0;
      for(let m=startM;m<12;m++){
        if(rem<months[m]){ return {y:y,m:m,d:rem+1}; }
        rem-=months[m];
      }
    }
  }
  return {y:2082,m:0,d:1};
}

function isLeap(y){ return (y%4===0&&y%100!==0)||(y%400===0); }
function daysInAdMonth(y,m0){
  const days=[31,isLeap(y)?29:28,31,30,31,30,31,31,30,31,30,31];
  return days[m0];
}

// Tithi names (approximate, based on lunar cycle position)
const TITHI_NAMES=['प्रतिपदा','द्वितीया','तृतीया','चतुर्थी','पञ्चमी','षष्ठी','सप्तमी','अष्टमी','नवमी','दशमी','एकादशी','द्वादशी','त्रयोदशी','चतुर्दशी','पूर्णिमा','प्रतिपदा','द्वितीया','तृतीया','चतुर्थी','पञ्चमी','षष्ठी','सप्तमी','अष्टमी','नवमी','दशमी','एकादशी','द्वादशी','त्रयोदशी','चतुर्दशी','औंसी'];
function getTithiApprox(adDate){
  // Approximate: lunar cycle ~29.53 days, reference New Moon 2000-01-06
  const refNM=new Date(2000,0,6);
  const diff=Math.round((adDate-refNM)/86400000);
  const tithiIdx=Math.abs(Math.floor(diff%29.53));
  return TITHI_NAMES[tithiIdx%30];
}

/* ══════════════════════════════════════════════════════════════════
   APP STATE & DATA
══════════════════════════════════════════════════════════════════ */
const today=new Date(); today.setHours(0,0,0,0);
let curYear=today.getFullYear(), curMonth=today.getMonth();
let upFilter='all', nextId=20;

let EVENTS=[
  {id:1, title:'Loksewa Mock Test — Batch A',     type:'exam',    start:'2026-02-05', end:'2026-02-05', batch:'Morning Batch A', desc:'Full syllabus MCQ. 100 questions, 90 mins. Negative marking -0.25.'},
  {id:2, title:'Maha Shivaratri Holiday',          type:'holiday', start:'2026-02-26', end:'2026-02-26', batch:'All',             desc:'National public holiday. No classes today.'},
  {id:3, title:'Fee Installment Due',              type:'fee',     start:'2026-02-15', end:'2026-02-15', batch:'All',             desc:'Monthly installment due. Late fine Rs 100/day.'},
  {id:4, title:'Banking Mock Test — Batch B',      type:'exam',    start:'2026-02-10', end:'2026-02-10', batch:'Day Batch B',     desc:'Banking & Finance paper. 75 questions, 60 min.'},
  {id:5, title:'New Batch Orientation',            type:'batch',   start:'2026-02-17', end:'2026-02-17', batch:'Evening Batch C', desc:'Orientation for new Evening Batch C students.'},
  {id:6, title:'Fee Slip Updated Notice',          type:'notice',  start:'2026-02-13', end:'2026-02-13', batch:'All',             desc:'New fee slips available in student portal.'},
  {id:7, title:'TSC Primary Full Mock',            type:'exam',    start:'2026-02-20', end:'2026-02-20', batch:'Morning Batch A', desc:'TSC Primary full mock — 3 subjects.'},
  {id:8, title:'Fee Due — March',                  type:'fee',     start:'2026-03-15', end:'2026-03-15', batch:'All',             desc:'March monthly installment deadline.'},
  {id:9, title:'Holi Holiday',                     type:'holiday', start:'2026-03-14', end:'2026-03-14', batch:'All',             desc:'Holi festival. Institute closed.'},
  {id:10,title:'Batch Midterm Exam',               type:'exam',    start:'2026-03-22', end:'2026-03-24', batch:'Morning Batch A', desc:'3-day midterm covering Months 1–4.'},
  {id:11,title:'Nepal New Year Holiday',           type:'holiday', start:'2026-04-14', end:'2026-04-14', batch:'All',             desc:'Nepali New Year BS 2083. Institute closed.'},
  {id:12,title:'Fee Due — April',                  type:'fee',     start:'2026-04-15', end:'2026-04-15', batch:'All',             desc:'April monthly installment.'},
];

const EV_CFG={
  exam:    {cls:'ev-exam',    icon:'fa-file-pen',       badge:'bg-b', label:'Exam',    bg:'#eff6ff', col:'#3b82f6'},
  holiday: {cls:'ev-holiday', icon:'fa-umbrella-beach', badge:'bg-r', label:'Holiday', bg:'#fde8ed', col:'#E11D48'},
  fee:     {cls:'ev-fee',     icon:'fa-rupee-sign',     badge:'bg-y', label:'Fee Due', bg:'#fef9e7', col:'#d97706'},
  batch:   {cls:'ev-batch',   icon:'fa-layer-group',    badge:'bg-t', label:'Batch',   bg:'#e0f5f0', col:'#009E7E'},
  notice:  {cls:'ev-notice',  icon:'fa-bullhorn',       badge:'bg-p', label:'Notice',  bg:'#F3E8FF', col:'#8141A5'},
};

function ds(y,m,d){ return `${y}-${String(m+1).padStart(2,'0')}-${String(d).padStart(2,'0')}` }
function pd(s){ const[y,m,d]=s.split('-').map(Number); return new Date(y,m-1,d); }
function fmtDate(s){ return pd(s).toLocaleDateString('en-NP',{day:'numeric',month:'short',year:'numeric'}); }
function evOnDate(dateStr){ const dt=pd(dateStr); return EVENTS.filter(e=>{ const st=pd(e.start),en=pd(e.end); return dt>=st&&dt<=en; }); }

/* ══════════════════════════════════════════════════════════════════
   TODAY BS BANNER
══════════════════════════════════════════════════════════════════ */
function renderTodayBanner(){
  // Check if required DOM elements exist
  const bsDay = document.getElementById('bsDay');
  const bsMonth = document.getElementById('bsMonth');
  const bsYear = document.getElementById('bsYear');
  const bsDayName = document.getElementById('bsDayName');
  const bsTithi = document.getElementById('bsTithi');
  const bsAd = document.getElementById('bsAd');
  
  if (!bsDay || !bsMonth || !bsYear || !bsDayName || !bsTithi || !bsAd) {
    return; // Elements not ready
  }
  
  const bs=adToBS_v2(today.getFullYear(),today.getMonth(),today.getDate());
  const dow=today.getDay();
  bsDay.textContent   = bs.d;
  bsMonth.textContent = `${BS_MONTHS_NP[bs.m]} ${bs.y}`;
  bsYear.textContent  = `BS ${bs.y}`;
  bsDayName.textContent = NP_DAYS[dow];
  bsTithi.textContent = getTithiApprox(today);
  bsAd.textContent    = today.toLocaleDateString('en-NP',{weekday:'short',day:'numeric',month:'long',year:'numeric'});
}

/* ══════════════════════════════════════════════════════════════════
   CALENDAR RENDER
══════════════════════════════════════════════════════════════════ */
function renderCal(){
  // Check if required DOM elements exist
  const grid = document.getElementById('calGrid');
  const monthLabel = document.getElementById('calMonthLabel');
  const monthSub = document.getElementById('calMonthSub');
  
  if (!grid || !monthLabel || !monthSub) {
    console.log('Calendar DOM not ready, skipping render');
    return;
  }
  
  const firstDay=new Date(curYear,curMonth,1).getDay();
  const daysInMonth=new Date(curYear,curMonth+1,0).getDate();
  const prevDays=new Date(curYear,curMonth,0).getDate();
  const todayStr=ds(today.getFullYear(),today.getMonth(),today.getDate());
  const bs0=adToBS_v2(curYear,curMonth,1);

  monthLabel.textContent=`${AD_MONTHS[curMonth]} ${curYear}`;
  monthSub.textContent=`${BS_MONTHS_EN[bs0.m]} / ${BS_MONTHS_EN[(bs0.m+1)%12]} ${bs0.y} BS`;

  let total=firstDay+daysInMonth;
  if(total%7!==0) total+=7-(total%7);
  let html='';

  for(let i=0;i<total;i++){
    let dayNum,m,y,other=false;
    const isSun=(i%7===0);
    if(i<firstDay){
      dayNum=prevDays-firstDay+i+1; m=curMonth-1; y=curYear;
      if(m<0){m=11;y--;} other=true;
    } else if(i>=firstDay+daysInMonth){
      dayNum=i-firstDay-daysInMonth+1; m=curMonth+1; y=curYear;
      if(m>11){m=0;y++;} other=true;
    } else {
      dayNum=i-firstDay+1; m=curMonth; y=curYear;
    }
    const dateKey=ds(y,m,dayNum);
    const isToday=dateKey===todayStr;
    const adDate=new Date(y,m,dayNum);
    const bs=adToBS_v2(y,m,dayNum);
    const tithi=getTithiApprox(adDate);
    const evs=evOnDate(dateKey);
    const hasHol=evs.some(e=>e.type==='holiday');

    let cls='cal-cell';
    if(other) cls+=' other-month';
    if(isToday) cls+=' today';
    if(isSun) cls+=' sunday';
    if(hasHol&&!other) cls+=' holiday';

    // BS date display
    const bsLabel=!other?`<div class="day-bs">${bs.d} ${BS_MONTHS_EN[bs.m].slice(0,3)}</div>`:'';
    const tithiLabel=!other&&!isSun?`<div class="day-tithi">${tithi}</div>`:'';

    let chips='';
    evs.slice(0,2).forEach(e=>{
      const cfg=EV_CFG[e.type];
      chips+=`<div class="ev-chip ${cfg.cls}" onclick="event.stopPropagation();showDetail(${e.id})" title="${e.title}"><i class="fa ${cfg.icon}"></i><span>${e.title}</span></div>`;
    });
    if(evs.length>2) chips+=`<div class="ev-more" onclick="event.stopPropagation();showMoreDay('${dateKey}')">+${evs.length-2} more</div>`;

    html+=`<div class="${cls}" onclick="onCellClick('${dateKey}')">
      <div class="day-num">${dayNum}</div>
      ${bsLabel}${tithiLabel}${chips}
    </div>`;
  }
  grid.innerHTML=html;
  renderUpcoming();
}

/* ══════════════════════════════════════════════════════════════════
   UPCOMING
══════════════════════════════════════════════════════════════════ */
function renderUpcoming(){
  const now=new Date(); now.setHours(0,0,0,0);
  let evs=EVENTS
    .filter(e=>{ const s=pd(e.start); s.setHours(0,0,0,0); return s>=now&&(upFilter==='all'||e.type===upFilter); })
    .sort((a,b)=>pd(a.start)-pd(b.start)).slice(0,12);

  if(!evs.length){
    document.getElementById('upcomingList').innerHTML=`<div style="text-align:center;padding:28px 16px;color:var(--tl);font-size:13px"><i class="fa fa-calendar-check" style="font-size:28px;opacity:.3;display:block;margin-bottom:8px"></i>No upcoming events</div>`;
    return;
  }
  document.getElementById('upcomingList').innerHTML=evs.map(e=>{
    const cfg=EV_CFG[e.type];
    const d=pd(e.start);
    const bs=adToBS_v2(d.getFullYear(),d.getMonth(),d.getDate());
    return `<div class="ev-item" onclick="showDetail(${e.id})">
      <div class="ev-date-box" style="background:${cfg.bg};color:${cfg.col}">
        <div class="ev-dd">${d.getDate()}</div>
        <div class="ev-mm">${AD_MONTHS[d.getMonth()].slice(0,3)}</div>
      </div>
      <div class="ev-body">
        <div class="ev-title">${e.title}</div>
        <div class="ev-meta">
          <span><i class="fa fa-layer-group"></i>${e.batch}</span>
          <span>·</span>
          <span>${BS_MONTHS_EN[bs.m]} ${bs.d}, ${bs.y} BS</span>
        </div>
      </div>
      <span class="ev-badge ${cfg.badge}">${cfg.label}</span>
    </div>`;
  }).join('');
}

/* ══════════════════════════════════════════════════════════════════
   NAV
══════════════════════════════════════════════════════════════════ */
function changeMonth(d){ curMonth+=d; if(curMonth>11){curMonth=0;curYear++;} if(curMonth<0){curMonth=11;curYear--;}  }
function goToday(){ curYear=today.getFullYear(); curMonth=today.getMonth(); renderCal(); showToast('Jumped to today','info'); }
function filterUpcoming(f,btn){ upFilter=f; document.querySelectorAll('.uf-btn').forEach(b=>b.classList.remove('active')); btn.classList.add('active'); renderUpcoming(); }

function onCellClick(dateKey){
  const evs=evOnDate(dateKey);
  if(!evs.length){ openAddModal(dateKey); return; }
  if(evs.length===1){ showDetail(evs[0].id); return; }
  showMoreDay(dateKey);
}

function showMoreDay(dateKey){
  const evs=evOnDate(dateKey);
  const d=pd(dateKey);
  const bs=adToBS_v2(d.getFullYear(),d.getMonth(),d.getDate());
  document.getElementById('detailTitle').textContent=`${EN_DAYS[d.getDay()]}, ${BS_MONTHS_EN[bs.m]} ${bs.d}`;
  document.getElementById('detailDeleteBtn').style.display='none';
  document.getElementById('detailBody').innerHTML=evs.map(e=>{
    const cfg=EV_CFG[e.type];
    return `<div class="ev-item" style="padding:10px 0;border-bottom:1px solid #f1f5f9;cursor:pointer" onclick="closeModal('detailModal');setTimeout(()=>showDetail(${e.id}),150)">
      <div class="ev-date-box" style="background:${cfg.bg};color:${cfg.col}"><i class="fa ${cfg.icon}" style="font-size:15px"></i></div>
      <div class="ev-body">
        <div style="margin-bottom:3px"><span class="tag ${cfg.badge}">${cfg.label}</span></div>
        <div class="ev-title">${e.title}</div>
        <div class="ev-meta"><i class="fa fa-layer-group"></i>${e.batch}</div>
      </div>
    </div>`;
  }).join('');
  openModal('detailModal');
}

function showDetail(id){
  const e=EVENTS.find(x=>x.id===id); if(!e) return;
  const cfg=EV_CFG[e.type];
  const d=pd(e.start);
  const bs=adToBS_v2(d.getFullYear(),d.getMonth(),d.getDate());
  const bsStr=`${BS_MONTHS_EN[bs.m]} ${bs.d}, ${bs.y} BS`;
  document.getElementById('detailTitle').innerHTML=`<i class="fa ${cfg.icon}" style="color:${cfg.col};margin-right:6px"></i> Event Detail`;
  document.getElementById('detailDeleteBtn').style.display='';
  document.getElementById('detailDeleteBtn').onclick=()=>deleteEvent(id);
  document.getElementById('detailBody').innerHTML=`
    <div class="ev-detail-banner" style="background:${cfg.bg}">
      <div class="ev-detail-icon" style="background:${cfg.col}22;color:${cfg.col}"><i class="fa ${cfg.icon}"></i></div>
      <div><div class="ev-detail-title">${e.title}</div><div class="ev-detail-sub"><span class="tag ${cfg.badge}">${cfg.label}</span></div></div>
    </div>
    <div class="ev-detail-row"><i class="fa fa-calendar-day"></i><span class="ev-detail-key">AD Date</span><span class="ev-detail-val">${fmtDate(e.start)}</span></div>
    <div class="ev-detail-row"><i class="fa fa-sun"></i><span class="ev-detail-key">BS Date</span><span class="ev-detail-val">${bsStr}</span></div>
    <div class="ev-detail-row"><i class="fa fa-calendar-check"></i><span class="ev-detail-key">End Date</span><span class="ev-detail-val">${e.start===e.end?'Same day':fmtDate(e.end)}</span></div>
    <div class="ev-detail-row"><i class="fa fa-layer-group"></i><span class="ev-detail-key">Batch</span><span class="ev-detail-val">${e.batch}</span></div>
    ${e.desc?`<div class="ev-detail-row"><i class="fa fa-align-left"></i><span class="ev-detail-key">Details</span><span class="ev-detail-val">${e.desc}</span></div>`:''}
  `;
  openModal('detailModal');
}

/* ══════════════════════════════════════════════════════════════════
   ADD / SAVE / DELETE
══════════════════════════════════════════════════════════════════ */
function openAddModal(prefill){
  document.getElementById('evTitle').value='';
  document.getElementById('evType').value='exam';
  document.getElementById('evBatch').value='All';
  document.getElementById('evDesc').value='';
  const d=prefill||ds(curYear,curMonth,today.getDate());
  document.getElementById('evStart').value=d;
  document.getElementById('evEnd').value=d;
  openModal('addModal');
}
function saveEvent(){
  const title=document.getElementById('evTitle').value.trim();
  const start=document.getElementById('evStart').value;
  const end=document.getElementById('evEnd').value||start;
  if(!title){showToast('Please enter event title','warn');return;}
  if(!start){showToast('Please select start date','warn');return;}
  EVENTS.push({id:nextId++,title,type:document.getElementById('evType').value,start,end,batch:document.getElementById('evBatch').value,desc:document.getElementById('evDesc').value.trim()});
  closeModal('addModal'); renderCal();
  showToast(`"${title}" added to calendar`,'success');
}
function deleteEvent(id){
  const e=EVENTS.find(x=>x.id===id);
  EVENTS=EVENTS.filter(x=>x.id!==id);
  closeModal('detailModal'); renderCal();
  showToast(`Deleted: "${e.title}"`,'warn');
}

/* ══════════════════════════════════════════════════════════════════
   HAMRO PATRO TABS
══════════════════════════════════════════════════════════════════ */
function showHpTab(id,btn){
  document.querySelectorAll('.hp-tab').forEach(t=>t.classList.remove('active'));
  document.querySelectorAll('.hp-pane').forEach(p=>p.classList.remove('active'));
  btn.classList.add('active');
  document.getElementById('hp-'+id).classList.add('active');
}

/* ══════════════════════════════════════════════════════════════════
   UTILS
══════════════════════════════════════════════════════════════════ */
function openModal(id){document.getElementById(id).classList.add('open');}
function closeModal(id){document.getElementById(id).classList.remove('open');}
// NOTE: modal-backdrop listeners are bound inside initAcademicCalendar() after DOM injection
function toggleSidebar(){document.body.classList.toggle('sb-active');if(window.innerWidth>=1024)document.body.classList.toggle('sb-collapsed');}
function showToast(msg,type='info'){
  const w=document.getElementById('toastWrap');
  const ic={success:'check-circle',info:'info-circle',warn:'exclamation-triangle'};
  const t=document.createElement('div');
  t.className=`toast ${type}`;
  t.innerHTML=`<i class="fa fa-${ic[type]||'info-circle'}"></i> ${msg}`;
  w.appendChild(t);
  setTimeout(()=>{t.style.animation='slideOut .3s ease-out forwards';setTimeout(()=>t.remove(),300);},3200);
}

// ── INIT ──

// Note: renderCal() is now called only after DOM is ready via initAcademicCalendar()

window.initAcademicCalendar = function() {
    // Bind modal-backdrop click-outside to close — must run AFTER HTML is injected into DOM
    document.querySelectorAll('.academic-calendar-wrap .modal-backdrop').forEach(m => {
        m.addEventListener('click', e => { if (e.target === m) m.classList.remove('open'); });
    });
    if (typeof renderTodayBanner === 'function') renderTodayBanner();
    if (typeof renderCal === 'function') renderCal();
    _iaFetchEvents(); // Fetch real events from API (falls back to mock data on failure)
};

// Overwrite saveEvent, deleteEvent, and add fetching logic
window._iaFetchEvents = async function() {
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/academic-calendar');
        // Check content-type before parsing — server might return HTML on error
        const ct = res.headers.get('content-type') || '';
        if (!res.ok || !ct.includes('application/json')) {
            console.warn('Academic calendar API unavailable (' + res.status + ') — using demo data.');
            // Keep the built-in mock EVENTS, just re-render
            if (typeof renderCal === 'function') renderCal();
            return;
        }
        const data = await res.json();
        if (data.success && Array.isArray(data.data)) {
            // Merge API events with mock data OR replace entirely
            window.EVENTS = data.data.length > 0 ? data.data : window.EVENTS;
            if (typeof renderCal === 'function') renderCal();
        }
    } catch(err) {
        console.warn('Failed to load calendar events (using demo data):', err.message);
        // Always ensure calendar renders with mock data as fallback
        if (typeof renderCal === 'function') renderCal();
    }
};

window.saveEvent = async function() {
    const title = document.getElementById('evTitle').value.trim();
    const start = document.getElementById('evStart').value;
    const end = document.getElementById('evEnd').value || start;
    if(!title) { showToast('Please enter event title','warn'); return; }
    if(!start) { showToast('Please select start date','warn'); return; }

    const payload = {
        title,
        type: document.getElementById('evType').value,
        start,
        end,
        batch: document.getElementById('evBatch').value,
        description: document.getElementById('evDesc').value.trim()
    };

    try {
        const res = await fetch(APP_URL + '/api/frontdesk/academic-calendar/save', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            closeModal('addModal');
            _iaFetchEvents();
            showToast(`"${title}" added to calendar`, 'success');
        } else {
            showToast(data.message || 'Error saving event', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}

window.deleteEvent = async function(id) {
    if(!confirm('Are you sure you want to delete this event?')) return;
    try {
        const res = await fetch(APP_URL + '/api/frontdesk/academic-calendar/delete?id='+id, { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            closeModal('detailModal');
            _iaFetchEvents();
            showToast('Event deleted', 'warn');
        } else {
            showToast(data.message || 'Error deleting', 'error');
        }
    } catch (err) {
        showToast('Network error', 'error');
    }
}
