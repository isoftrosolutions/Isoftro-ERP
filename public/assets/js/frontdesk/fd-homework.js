/**
 * iSoftro ERP — Institute Admin · ia-homework.js
 * Homework Module Javascript
 */

window.renderHomeworkList = function () {
  const mc = document.getElementById("mainContent");
  if (!mc) return;

  // Build the initial HTML structure
  mc.innerHTML = `
        <div class="pg">
            <div class="pg-hdr">
                <div class="pg-title">Homework Assignments</div>
                <div class="pg-actions">
                    <button class="btn primary" onclick="goNav('homework', 'create')"><i class="fa fa-plus"></i> Assign</button>
                    <button class="btn secondary" onclick="loadHomeworkData()"><i class="fa fa-sync"></i> Refresh</button>
                </div>
            </div>
            
            <div class="filters" style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap">
                <select id="hwCourse" class="form-control" style="width:200px" onchange="iaHwLoadBatches()">
                    <option value="">All Courses</option>
                </select>
                <select id="hwBatch" class="form-control" style="width:200px" onchange="loadHomeworkData()">
                    <option value="">All Batches</option>
                </select>
                <select id="hwStatus" class="form-control" style="width:150px" onchange="loadHomeworkData()">
                    <option value="">All Statuses</option>
                    <option value="published">Published</option>
                    <option value="draft">Draft</option>
                    <option value="closed">Closed</option>
                </select>
            </div>
            
            <div class="card">
                <div class="card-body" style="padding:0">
                    <div class="table-responsive">
                        <table class="table table-hover" id="hwTable">
                            <thead>
                                <tr>
                                    <th>Title</th>
                                    <th>Course/Batch</th>
                                    <th>Subject</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="hwTbody">
                                <tr><td colspan="6" style="text-align:center; padding:20px"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    `;

  // Load initial data
  iaHwLoadCourses();
  setTimeout(loadHomeworkData, 100);
};

window.iaHwLoadCourses = async function () {
  try {
    const url = APP_URL + "/api/frontdesk/courses";
    const res = await fetch(url);
    const data = await res.json();
    if (data.success && data.data) {
      const sel = document.getElementById("hwCourse");
      if (sel) {
        let html = '<option value="">All Courses</option>';
        data.data.forEach(
          (c) => (html += `<option value="${c.id}">${c.name}</option>`),
        );
        sel.innerHTML = html;
      }
    }
  } catch (e) {
    console.error("Error loading courses:", e);
  }
};

window.iaHwLoadBatches = async function () {
  const courseId = document.getElementById("hwCourse")?.value;
  const sel = document.getElementById("hwBatch");
  if (!sel) return;

  if (!courseId) {
    sel.innerHTML = '<option value="">All Batches</option>';
    loadHomeworkData();
    return;
  }

  try {
    const url = APP_URL + "/api/frontdesk/batches?course_id=" + courseId;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success && data.data) {
      let html = '<option value="">All Batches</option>';
      data.data.forEach(
        (b) => (html += `<option value="${b.id}">${b.name}</option>`),
      );
      sel.innerHTML = html;
    }
  } catch (e) {
    console.error("Error loading batches:", e);
  }
  loadHomeworkData();
};

window.loadHomeworkData = async function () {
  const tbody = document.getElementById("hwTbody");
  if (!tbody) return;

  const courseId = document.getElementById("hwCourse")?.value || "";
  const batchId = document.getElementById("hwBatch")?.value || "";
  const status = document.getElementById("hwStatus")?.value || "";

  tbody.innerHTML =
    '<tr><td colspan="6" style="text-align:center; padding:20px"><i class="fa fa-spinner fa-spin"></i> Loading...</td></tr>';

  try {
    /*
     * Note: API endpoint for homework will be created next
     */
    const url =
      APP_URL +
      `/api/frontdesk/homework?course_id=${courseId}&batch_id=${batchId}&status=${status}`;
    const res = await fetch(url);

    // Handle mock response or actual response properly
    if (res.status === 404) {
      tbody.innerHTML =
        '<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-light)">Endpoint /api/frontdesk/homework not created yet.</td></tr>';
      return;
    }

    const data = await res.json();
    if (data.success) {
      if (data.homework && data.homework.length > 0) {
        let html = "";
        data.homework.forEach((hw) => {
          html += `
                        <tr>
                            <td>
                                <div style="font-weight:600">${hw.title}</div>
                                <div style="font-size:12px;color:var(--text-light)">${hw.total_marks ? hw.total_marks + " Marks" : ""}</div>
                            </td>
                            <td>
                                <div>${hw.course_name}</div>
                                <div style="font-size:12px;color:var(--text-light)">${hw.batch_name}</div>
                            </td>
                            <td>${hw.subject_name}</td>
                            <td>
                                <div ${new Date(hw.due_date) < new Date() && hw.status !== "closed" ? 'style="color:var(--red);font-weight:600"' : ""}>${hw.due_date}</div>
                            </td>
                            <td><span class="badge" style="background:${hw.status === "published" ? "var(--green)" : hw.status === "closed" ? "var(--text-light)" : "var(--orange)"};color:#fff">${hw.status}</span></td>
                            <td>
                                <button class="btn-icon" title="View"><i class="fa fa-eye"></i></button>
                                <button class="btn-icon" title="Edit"><i class="fa fa-edit"></i></button>
                                <button class="btn-icon danger" title="Delete"><i class="fa fa-trash"></i></button>
                            </td>
                        </tr>
                    `;
        });
        tbody.innerHTML = html;
      } else {
        tbody.innerHTML =
          '<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--text-light)">No homework found matching criteria.</td></tr>';
      }
    } else {
      tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--red)">Failed to load data: ${data.message || "Unknown error"}</td></tr>`;
    }
  } catch (e) {
    console.error("Error loading homework:", e);
    tbody.innerHTML = `<tr><td colspan="6" style="text-align:center; padding:20px; color:var(--red)">Error communicating with server.</td></tr>`;
  }
};

window.renderCreateHomeworkForm = function () {
  const mc = document.getElementById("mainContent");
  if (!mc) return;

  mc.innerHTML = `
        <div class="pg">
            <div class="pg-hdr">
                <div class="pg-title"><i class="fa fa-arrow-left" style="cursor:pointer; margin-right:10px" onclick="goNav('homework', 'list')"></i> Assign Homework</div>
            </div>
            
            <div class="card" style="max-width:800px; margin:0 auto;">
                <div class="card-body">
                    <form id="hwForm" onsubmit="submitHomework(event)">
                        <div class="row" style="margin-bottom:15px">
                            <div class="col-md-6 form-group">
                                <label>Course <span class="text-danger">*</span></label>
                                <select name="course_id" id="formCourse" class="form-control" required onchange="iaHwFormLoadBatches()">
                                    <option value="">Select Course</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Batch <span class="text-danger">*</span></label>
                                <select name="batch_id" id="formBatch" class="form-control" required onchange="iaHwFormLoadSubjects()">
                                    <option value="">Select Batch</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row" style="margin-bottom:15px">
                            <div class="col-md-12 form-group">
                                <label>Subject <span class="text-danger">*</span></label>
                                <select name="subject_id" id="formSubject" class="form-control" required>
                                    <option value="">Select Subject</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row" style="margin-bottom:15px">
                            <div class="col-md-12 form-group">
                                <label>Homework Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g. Chapter 4 Exercises">
                            </div>
                        </div>
                        
                        <div class="row" style="margin-bottom:15px">
                            <div class="col-md-12 form-group">
                                <label>Description/Instructions</label>
                                <textarea name="description" class="form-control" rows="5" placeholder="Detailed instructions for the assignment..."></textarea>
                            </div>
                        </div>
                        
                        <div class="row" style="margin-bottom:15px">
                            <div class="col-md-6 form-group">
                                <label>Due Date <span class="text-danger">*</span></label>
                                <input type="date" name="due_date" class="form-control" required>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Total Marks</label>
                                <input type="number" name="total_marks" class="form-control" value="100" min="0">
                            </div>
                        </div>
                        
                        <div class="row" style="margin-bottom:20px">
                            <div class="col-md-6 form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="published">Published (Visible to students immediately)</option>
                                    <option value="draft">Draft (Save for later)</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label>Attachment (Optional)</label>
                                <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            </div>
                        </div>
                        
                        <div style="text-align:right">
                            <button type="button" class="btn secondary" onclick="goNav('homework', 'list')">Cancel</button>
                            <button type="submit" class="btn primary" id="btnSubmit">Save Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;

  // Load initial dropdowns
  iaHwFormLoadCourses();
};

window.iaHwFormLoadCourses = async function () {
  try {
    const url = APP_URL + "/api/frontdesk/courses";
    const res = await fetch(url);
    const data = await res.json();
    if (data.success && data.data) {
      const sel = document.getElementById("formCourse");
      if (sel) {
        let html = '<option value="">Select Course</option>';
        data.data.forEach(
          (c) => (html += `<option value="${c.id}">${c.name}</option>`),
        );
        sel.innerHTML = html;
      }
    }
  } catch (e) {
    console.error("Error:", e);
  }
};

window.iaHwFormLoadBatches = async function () {
  const courseId = document.getElementById("formCourse")?.value;
  const sel = document.getElementById("formBatch");
  if (!sel) return;

  sel.innerHTML = '<option value="">Select Batch</option>';
  document.getElementById("formSubject").innerHTML =
    '<option value="">Select Subject</option>';

  if (!courseId) return;

  try {
    const url = APP_URL + "/api/frontdesk/batches?course_id=" + courseId;
    const res = await fetch(url);
    const data = await res.json();
    if (data.success && data.data) {
      let html = '<option value="">Select Batch</option>';
      data.data.forEach(
        (b) => (html += `<option value="${b.id}">${b.name}</option>`),
      );
      sel.innerHTML = html;
    }
  } catch (e) {
    console.error("Error:", e);
  }
};

window.iaHwFormLoadSubjects = async function () {
  const courseId = document.getElementById("formCourse")?.value;
  const batchId = document.getElementById("formBatch")?.value;
  const sel = document.getElementById("formSubject");
  if (!sel) return;

  sel.innerHTML = '<option value="">Select Subject</option>';

  if (!courseId || !batchId) return;

  // Fallback logic for subjects if specific batch-subject mapping endpoint doesn't exist
  // Initially just loading course subjects
  try {
    const url = APP_URL + "/api/frontdesk/subjects?course_id=" + courseId;
    const res = await fetch(url);

    if (res.status === 404) {
      console.warn("Subjects endpoint missing, trying fallback mock");
      let html =
        '<option value="">Select Subject</option><option value="1">Mathematics</option><option value="2">Science</option><option value="3">English</option>';
      sel.innerHTML = html;
      return;
    }

    const data = await res.json();
    if (data.success && data.data) {
      let html = '<option value="">Select Subject</option>';
      data.data.forEach(
        (s) =>
          (html += `<option value="${s.id}">${s.name} (${s.code || "-"})</option>`),
      );
      sel.innerHTML = html;
    }
  } catch (e) {
    console.error("Error:", e);
    let html =
      '<option value="">Select Subject</option><option value="1">Mathematics</option><option value="2">Science</option><option value="3">English</option>';
    sel.innerHTML = html;
  }
};

window.submitHomework = async function (e) {
  e.preventDefault();
  const btn = document.getElementById("btnSubmit");
  const originalText = btn.innerHTML;
  btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving...';
  btn.disabled = true;

  const form = document.getElementById("hwForm");
  const formData = new FormData(form);

  try {
    const url = APP_URL + "/api/frontdesk/homework/store";
    const res = await fetch(url, {
      method: "POST",
      body: formData,
      headers: {
        "X-CSRF-Token":
          window.CSRF_TOKEN ||
          window.csrfToken ||
          document.querySelector('meta[name="csrf-token"]')?.content,
      },
    });

    if (res.status === 404) {
      alert("Store endpoint not available yet");
      btn.innerHTML = originalText;
      btn.disabled = false;
      return;
    }

    const data = await res.json();
    if (data.success) {
      // Check if Swal is available, otherwise alert
      if (typeof Swal !== "undefined") {
        Swal.fire({
          icon: "success",
          title: "Saved!",
          text: "Homework assignment has been saved successfully.",
          timer: 1500,
          showConfirmButton: false,
        }).then(() => {
          goNav("homework", "list");
        });
      } else {
        alert("Homework assignment saved successfully!");
        goNav("homework", "list");
      }
    } else {
      alert("Error: " + (data.message || "Failed to save homework."));
    }
  } catch (err) {
    console.error("Submit error:", err);
    alert("An error occurred while saving.");
  } finally {
    if (document.getElementById("btnSubmit")) {
      btn.innerHTML = originalText;
      btn.disabled = false;
    }
  }
};
