<template>
  <div class="modal-backdrop" @click.self="$emit('cancel')">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Add book">
      <header class="modal-header">
        <h3>Add a new book</h3>
        <button class="icon" @click="$emit('cancel')" aria-label="Close">×</button>
      </header>

      <section class="modal-body">
        <div class="grid">
          <div>
            <label>Title</label>
            <input
              ref="title"
              v-model.trim="form.title"
              @keyup.enter.exact.prevent="save"
              placeholder="Required"
              autofocus
            />

            <label>Subtitle</label>
            <input v-model.trim="form.subtitle" />

            <label>Series</label>
            <input v-model.trim="form.series" />

            <label>Publisher (name)</label>
            <div class="combo">
              <input
                v-model.trim="pubQuery"
                @input="onPubInput"
                placeholder="Type to search…"
                autocomplete="off"
              />
              <ul v-if="pubOptions.length" class="combo-list">
                <li v-for="o in pubOptions" :key="o.id" @click="choosePublisher(o)">
                  {{ o.name }}
                </li>
              </ul>
            </div>

            <label>Year Published</label>
            <input v-model.number="form.year_published" type="number" min="0" max="3000" />
          </div>

          <div>
            <label>ISBN</label>
            <input v-model.trim="form.isbn" />

            <label>Authors</label>
            <div class="combo" @keydown.esc="closeAuthorList">
              <input
                v-model.trim="form.authors"
                @input="onAuthorsInput"
                @focus="onAuthorsFocus"
                placeholder="Type to search"
                autocomplete="off"
                aria-autocomplete="list"
                aria-expanded="true"
              />
              <ul v-if="showAuthorList" class="combo-list">
                <li v-for="o in authorOptions" :key="o.id" @click="chooseAuthor(o)">
                  {{ o.name }}
                </li>
                <li v-if="allowAuthorCreate" class="create" @click="openAuthorCreate">
                  Add new author…
                </li>
              </ul>
            </div>
            <label class="inline">
              <input type="checkbox" v-model="form.authors_is_hungarian" />
              Hungarian name order (Last First)
            </label>

            <label>Placement (bookcase / shelf)</label>
            <div class="placement-row">
              <input v-model.number="placement.bookcase_no" type="number" placeholder="Bookcase no" />
              <input v-model.number="placement.shelf_no"   type="number" placeholder="Shelf no" />
            </div>

            <label>Notes</label>
            <textarea v-model.trim="form.notes" rows="3"></textarea>
          </div>
        </div>
      </section>

      <footer class="modal-footer">
        <button @click="$emit('cancel')">Close</button>
        <button class="primary" :disabled="!canCreate" @click="save">Create</button>
      </footer>
    </div>

    <div v-if="authorCreateOpen" class="modal-backdrop" @click.self="authorCreateOpen = false">
      <div class="modal narrow" role="dialog" aria-modal="true" aria-label="Create author">
        <header class="modal-header">
          <h3>New author</h3>
          <button class="icon" @click="authorCreateOpen = false" aria-label="Close">×</button>
        </header>
        <section class="modal-body">
          <label>Name</label>
          <input v-model.trim="authorDraft.name" placeholder="Display name" />

          <label>First name</label>
          <input v-model.trim="authorDraft.first_name" />

          <label>Last name</label>
          <input v-model.trim="authorDraft.last_name" />

          <label>Sort name</label>
          <input v-model.trim="authorDraft.sort_name" placeholder="Last, First" />

          <label class="inline">
            <input type="checkbox" v-model="authorDraft.is_hungarian" />
            Hungarian name order (Last First)
          </label>
        </section>
        <footer class="modal-footer">
          <button @click="authorCreateOpen = false">Cancel</button>
          <button class="primary" @click="saveNewAuthor">Save author</button>
        </footer>
      </div>
    </div>
  </div>
</template>

<script lang="js">
import { suggestPublishers, suggestAuthors } from "../api";

export default {
  name: "AddBook",
  data() {
    return {
      form: {
        title: "",
        subtitle: "",
        series: "",
        publisher: "",
        publisher_id: null,
        year_published: null,
        isbn: "",
        authors: "",
        authors_is_hungarian: false,
        notes: "",
      },
      placement: { bookcase_no: null, shelf_no: null },
      pubQuery: "",
      pubOptions: [],
      pubTimer: null,
      authorOptions: [],
      authorTimer: null,
      showAuthorList: false,
      authorCreateOpen: false,
      authorDraft: {
        name: '',
        first_name: '',
        last_name: '',
        sort_name: '',
        is_hungarian: false,
      },
      keyHandler: null,
    };
  },
  computed: {
    canCreate() {
      return !!(this.form.title && this.form.title.trim());
    },
    allowAuthorCreate() {
      const q = (this.form.authors || '').trim();
      return q.length >= 2;
    },
  },
  mounted() {
    // focus title on open
    this.$nextTick(() => {
      try {
        const input = this.$refs.title;
        if (input && typeof input.focus === "function") input.focus();
      } catch {
        /* no-op */
      }
    });
    // ESC to close
    this.keyHandler = (e) => { if (e.key === 'Escape') this.$emit('cancel'); };
    window.addEventListener('keydown', this.keyHandler);
  },
  beforeUnmount() {
    window.removeEventListener('keydown', this.keyHandler);
  },
  watch: {
    "form.publisher"(v) {
      // keep the search box in sync if user pastes a name
      this.pubQuery = v || "";
    },
  },
  methods: {
    payload() {
      const p = { ...this.form };

      // placement only if both numbers present
      if (
        this.placement.bookcase_no !== null &&
        this.placement.shelf_no !== null &&
        this.placement.bookcase_no !== "" &&
        this.placement.shelf_no !== ""
      ) {
        p.placement = {
          bookcase_no: Number(this.placement.bookcase_no),
          shelf_no: Number(this.placement.shelf_no),
        };
      }

      // trim empty fields that could confuse backend
      if (!p.publisher) delete p.publisher;
      if (p.publisher_id == null || p.publisher_id === "") delete p.publisher_id;
      if (!p.authors) delete p.authors;
      if (!p.authors) delete p.authors_is_hungarian;
      if (!p.notes) delete p.notes;
      if (p.year_published === '' || p.year_published === null || p.year_published === undefined) {
        p.year_published = null;
      } else {
        const y = parseInt(p.year_published, 10);
        p.year_published = Number.isFinite(y) ? y : null;
      }

      return p;
    },

    save() {
      if (!this.canCreate) return; // button already disabled
      this.$emit("create", this.payload());
    },

    // --- publisher suggest ---
    onPubInput() {
      this.form.publisher_id = null; // typing invalidates chosen id
      clearTimeout(this.pubTimer);
      const q = this.pubQuery.trim();
      if (q.length < 2) { this.pubOptions = []; return; }
      this.pubTimer = setTimeout(async () => {
        try {
          this.pubOptions = await suggestPublishers(q);
        } catch {
          this.pubOptions = [];
        }
      }, 150);
    },
    choosePublisher(opt) {
      this.form.publisher_id = opt.id;
      this.form.publisher    = opt.name;
      this.pubQuery          = opt.name;
      this.pubOptions        = [];
    },

    // --- author suggest (simple helper) ---
    onAuthorsInput() {
      clearTimeout(this.authorTimer);
      const q = this.form.authors.trim();
      if (q.length < 2) { this.authorOptions = []; this.showAuthorList = false; return; }
      const lastToken = q.split(/[;,]\s*/).pop();
      this.authorTimer = setTimeout(async () => {
        try {
          this.authorOptions = await suggestAuthors(lastToken);
          this.showAuthorList = true;
        } catch {
          this.authorOptions = [];
          this.showAuthorList = false;
        }
      }, 150);
    },
    onAuthorsFocus() {
      if (this.authorOptions.length) {
        this.showAuthorList = true;
      } else if ((this.form.authors || '').trim().length >= 2) {
        this.onAuthorsInput();
      }
    },
    closeAuthorList() {
      this.showAuthorList = false;
    },
    chooseAuthor(opt) {
      const parts = this.form.authors.split(/[;,]\s*/).filter(Boolean);
      parts.pop();
      parts.push(opt.name);
      this.form.authors = parts.join("; ");
      this.authorOptions = [];
      this.showAuthorList = false;
    },
    openAuthorCreate() {
      const seed = (this.form.authors || '').trim().split(/[;,]\s*/).pop() || '';
      this.authorDraft = {
        name: seed,
        first_name: '',
        last_name: '',
        sort_name: '',
        is_hungarian: false,
      };
      if (seed.includes(',')) {
        const parts = seed.split(',');
        this.authorDraft.last_name = (parts[0] || '').trim();
        this.authorDraft.first_name = (parts.slice(1).join(' ') || '').trim();
        this.authorDraft.is_hungarian = true;
      }
      this.authorCreateOpen = true;
      this.showAuthorList = false;
    },
    async saveNewAuthor() {
      try {
        const payload = { ...this.authorDraft };
        if (!payload.name) {
          const first = (payload.first_name || '').trim();
          const last = (payload.last_name || '').trim();
          payload.name = payload.is_hungarian
            ? `${last} ${first}`.trim()
            : `${first} ${last}`.trim();
        }
        if (!payload.sort_name) {
          const first = (payload.first_name || '').trim();
          const last = (payload.last_name || '').trim();
          payload.sort_name = first && last ? `${last}, ${first}` : (last || first || '');
        }
        const { createAuthor } = await import('../api');
        const res = await createAuthor(payload);
        const name = res && res.data ? res.data.name : '';
        this.chooseAuthor({ name });
        this.authorCreateOpen = false;
      } catch (e) {
        alert(e && e.message ? e.message : 'Create author failed.');
      }
    },
  },
};
</script>

<style scoped>
/* match BookDialog look & feel */
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; padding: 1rem; z-index: 1000; }
.modal { background:#fff; border-radius:.75rem; width:min(1000px, 96vw); max-height: 92vh; overflow:auto; box-shadow: 0 14px 44px rgba(0,0,0,.25); }
.modal.narrow { width: min(560px, 96vw); }
.modal-header, .modal-footer { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; border-bottom:1px solid #eee; }
.modal-footer { border-top:1px solid #eee; border-bottom:none; }
.modal-body { padding:1rem 1.25rem; }

/* form layout */
.grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:.75rem 1rem; }
label { color:#666; font-size:.9em; margin-top:.35rem; display:block; }
input, textarea { width:100%; padding:.5rem .6rem; border:1px solid #ddd; border-radius:6px; font:inherit; }
textarea { resize: vertical; }
.inline { display:flex; align-items:center; gap:.45rem; margin-top:.35rem; }
.inline input { width: auto; }

.placement-row { display:grid; grid-template-columns: 1fr 1fr; gap:.5rem; }

/* combo dropdowns */
.combo { position: relative; }
.combo-list { position: absolute; left: 0; right: 0; z-index: 20; background: #fff; border: 1px solid #ddd; border-radius: 8px; margin: .25rem 0 0; padding: .25rem 0; max-height: 220px; overflow: auto; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
.combo-list li { padding: .4rem .6rem; cursor: pointer; }
.combo-list li:hover { background: #f5f7fa; }
.combo-list li.create { font-style: italic; }

/* buttons */
button { padding:.5rem .8rem; border-radius:8px; border:1px solid var(--btn-border); background: var(--btn-bg); cursor:pointer; color: var(--btn-text); }
button.primary { background:#1a73e8; color:#fff; border-color:#1a73e8; }
button:hover { filter: brightness(.98); }
.icon { font-size:1.5rem; line-height:1; background:none; border:none; cursor:pointer; }
</style>
