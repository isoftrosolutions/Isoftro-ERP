/**
 * PhotoUpload - Handles drag-and-drop and preview for student photos.
 */
window.PhotoUpload = class PhotoUpload {
  constructor(containerId, options = {}) {
    this.container = document.getElementById(containerId);
    if (!this.container) return;
    this.previewId = options.previewId || 'photoPreview';
    this.inputId = options.inputId || 'stdPhotoInput';
    this.onUpload = options.onUpload || (() => {});
    
    this.init();
  }

  init() {
    this.render();
    this.dropZone = this.container.querySelector('.nexus-photo-upload');
    this.fileInput = this.container.querySelector(`#${this.inputId}`);
    this.preview = this.container.querySelector(`#${this.previewId}`);

    this.attachEvents();
  }

  render() {
    this.container.innerHTML = `
      <div class="nexus-photo-upload-wrapper">
        <div class="nexus-photo-upload" id="dropZone">
          <i class="fa-solid fa-camera fa-2x" style="color: var(--nexus-text-muted); margin-bottom: 10px;"></i>
          <span style="font-size: 12px; color: var(--nexus-text-muted); text-align: center; padding: 0 10px;">
            Drag & Drop or Click to Upload
          </span>
          <img id="${this.previewId}" class="nexus-photo-preview" style="display: none;">
          <input type="file" id="${this.inputId}" name="photo" accept="image/*" style="display: none;">
        </div>
        <p class="nexus-field-help">Recommended: 300x300px (JPG/PNG)</p>
      </div>
    `;
  }

  attachEvents() {
    this.dropZone.addEventListener('click', () => this.fileInput.click());

    this.fileInput.addEventListener('change', (e) => this.handleFiles(e.target.files));

    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
      this.dropZone.addEventListener(eventName, (e) => {
        e.preventDefault();
        e.stopPropagation();
      }, false);
    });

    ['dragenter', 'dragover'].forEach(eventName => {
      this.dropZone.addEventListener(eventName, () => this.dropZone.classList.add('drag-over'), false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
      this.dropZone.addEventListener(eventName, () => this.dropZone.classList.remove('drag-over'), false);
    });

    this.dropZone.addEventListener('drop', (e) => {
      const dt = e.dataTransfer;
      this.handleFiles(dt.files);
    });
  }

  handleFiles(files) {
    if (files.length > 0) {
      const file = files[0];
      if (file.type.startsWith('image/')) {
        const reader = new FileReader();
        reader.onload = (e) => {
          this.preview.src = e.target.result;
          this.preview.style.display = 'block';
          this.dropZone.classList.add('has-image');
          this.onUpload(file, e.target.result);
        };
        reader.readAsDataURL(file);
      }
    }
  }
}
