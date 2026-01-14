<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Orphan maintenance">
      <header class="modal-header">
        <h3>Orphan maintenance</h3>
        <div class="header-actions">
          <button class="ghost" @click="load">Refresh</button>
          <button class="icon" @click="$emit('close')" aria-label="Close">×</button>
        </div>
      </header>

      <section class="modal-body">
        <div v-if="loading" class="muted">Loading…</div>

        <template v-else>
          <div class="section">
            <h4>Orphan authors ({{ orphanAuthors.length }})</h4>
            <table class="table" v-if="orphanAuthors.length">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>HU</th>
                  <th>Name</th>
                  <th>First</th>
                  <th>Last</th>
                  <th>Sort</th>
                  <th class="w-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="a in orphanAuthors" :key="`a-${a.author_id}`">
                  <td>{{ a.author_id }}</td>
                  <td>
                    <input
                      v-if="editingAuthorId === a.author_id"
                      type="checkbox"
                      v-model="authorDraft.is_hungarian"
                    />
                    <span v-else>{{ a.is_hungarian ? 'Yes' : 'No' }}</span>
                  </td>
                  <td>
                    <input v-if="editingAuthorId === a.author_id" :value="computedAuthorName" disabled />
                    <span v-else>{{ a.name || '—' }}</span>
                  </td>
                  <td>
                    <input v-if="editingAuthorId === a.author_id" v-model="authorDraft.first_name" />
                    <span v-else>{{ a.first_name || '—' }}</span>
                  </td>
                  <td>
                    <input v-if="editingAuthorId === a.author_id" v-model="authorDraft.last_name" />
                    <span v-else>{{ a.last_name || '—' }}</span>
                  </td>
                  <td>
                    <input v-if="editingAuthorId === a.author_id" :value="computedAuthorSort" disabled />
                    <span v-else>{{ a.sort_name || '—' }}</span>
                  </td>
                  <td class="actions">
                    <button v-if="editingAuthorId === a.author_id" @click="saveAuthor(a.author_id)">Save</button>
                    <button v-if="editingAuthorId === a.author_id" class="ghost" @click="cancelEditAuthor">Cancel</button>
                    <button v-else @click="editAuthor(a)">Edit</button>
                    <button class="danger" @click="deleteAuthor(a.author_id)">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-else class="muted">No orphan authors.</div>
          </div>

          <div class="section">
            <h4>Orphan publishers ({{ orphanPublishers.length }})</h4>
            <table class="table" v-if="orphanPublishers.length">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Name</th>
                  <th class="w-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="p in orphanPublishers" :key="`p-${p.publisher_id}`">
                  <td>{{ p.publisher_id }}</td>
                  <td>
                    <input v-if="editingPublisherId === p.publisher_id" v-model="publisherDraft.name" />
                    <span v-else>{{ p.name || '—' }}</span>
                  </td>
                  <td class="actions">
                    <button v-if="editingPublisherId === p.publisher_id" @click="savePublisher(p.publisher_id)">Save</button>
                    <button v-if="editingPublisherId === p.publisher_id" class="ghost" @click="cancelEditPublisher">Cancel</button>
                    <button v-else @click="editPublisher(p)">Edit</button>
                    <button class="danger" @click="deletePublisher(p.publisher_id)">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-else class="muted">No orphan publishers.</div>
          </div>

          <div class="section">
            <h4>Orphan book-author links ({{ orphanLinks.length }})</h4>
            <table class="table" v-if="orphanLinks.length">
              <thead>
                <tr>
                  <th>Book ID</th>
                  <th>Author ID</th>
                  <th>Book</th>
                  <th>Author</th>
                  <th class="w-actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="l in orphanLinks" :key="`l-${l.book_id}-${l.author_id}`">
                  <td>{{ l.book_id }}</td>
                  <td>{{ l.author_id }}</td>
                  <td>{{ l.book_title || 'Missing book' }}</td>
                  <td>{{ l.author_name || 'Missing author' }}</td>
                  <td class="actions">
                    <button @click="reassignLink(l.book_id, l.author_id)">Reassign</button>
                    <button class="danger" @click="deleteLink(l.book_id, l.author_id)">Delete</button>
                  </td>
                </tr>
              </tbody>
            </table>
            <div v-else class="muted">No orphan links.</div>
          </div>
        </template>
      </section>

      <footer class="modal-footer">
        <button @click="$emit('close')">Close</button>
      </footer>
    </div>
  </div>
</template>

<script lang="ts">
import {
  fetchOrphanMaintenance,
  deleteOrphanAuthor,
  deleteOrphanPublisher,
  deleteOrphanLink,
  reassignOrphanLink,
  updateOrphanAuthor,
  updateOrphanPublisher,
} from '../api';

type OrphanAuthor = {
  author_id: number;
  name?: string | null;
  first_name?: string | null;
  last_name?: string | null;
  sort_name?: string | null;
  is_hungarian?: boolean | number | null;
};

type OrphanPublisher = {
  publisher_id: number;
  name?: string | null;
};

type OrphanLink = {
  book_id: number;
  author_id: number;
  book_title?: string | null;
  author_name?: string | null;
};

export default {
  name: 'OrphanMaintenance',
  data() {
    return {
      loading: false,
      orphanAuthors: [] as OrphanAuthor[],
      orphanPublishers: [] as OrphanPublisher[],
      orphanLinks: [] as OrphanLink[],
      editingAuthorId: null as number | null,
      authorDraft: { first_name: '', last_name: '', is_hungarian: false },
      editingPublisherId: null as number | null,
      publisherDraft: { name: '' },
    };
  },
  computed: {
    computedAuthorName() {
      const first = (this.authorDraft.first_name || '').trim();
      const last = (this.authorDraft.last_name || '').trim();
      if (!first && !last) return '';
      return this.authorDraft.is_hungarian
        ? `${last} ${first}`.trim()
        : `${first} ${last}`.trim();
    },
    computedAuthorSort() {
      const first = (this.authorDraft.first_name || '').trim();
      const last = (this.authorDraft.last_name || '').trim();
      if (!first && !last) return '';
      if (!first) return last;
      if (!last) return first;
      return `${last}, ${first}`;
    },
  },
  mounted() {
    this.load();
  },
  methods: {
    errorMessage(err: unknown) {
      return err instanceof Error ? err.message : '';
    },
    async load() {
      this.loading = true;
      try {
        const data = await fetchOrphanMaintenance();
        const payload = data && data.data ? data.data : {};
        this.orphanAuthors = (payload.orphan_authors || []) as OrphanAuthor[];
        this.orphanPublishers = (payload.orphan_publishers || []) as OrphanPublisher[];
        this.orphanLinks = (payload.orphan_links || []) as OrphanLink[];
      } catch {
        alert('Failed to load orphan maintenance data.');
      } finally {
        this.loading = false;
      }
    },
    async deleteAuthor(authorId: number) {
      if (!confirm(`Delete orphan author #${authorId}?`)) return;
      try {
        await deleteOrphanAuthor(authorId);
        await this.load();
      } catch (e) {
        alert(this.errorMessage(e) || 'Delete failed.');
      }
    },
    async deletePublisher(publisherId: number) {
      if (!confirm(`Delete orphan publisher #${publisherId}?`)) return;
      try {
        await deleteOrphanPublisher(publisherId);
        await this.load();
      } catch (e) {
        alert(this.errorMessage(e) || 'Delete failed.');
      }
    },
    async deleteLink(bookId: number, authorId: number) {
      if (!confirm(`Delete orphan link book #${bookId} → author #${authorId}?`)) return;
      try {
        await deleteOrphanLink(bookId, authorId);
        await this.load();
      } catch (e) {
        alert(this.errorMessage(e) || 'Delete failed.');
      }
    },
    async reassignLink(bookId: number, authorId: number) {
      const input = prompt(`New author_id for book #${bookId} (was ${authorId}):`);
      if (input == null) return;
      const newAuthorId = parseInt(String(input).trim(), 10);
      if (!Number.isFinite(newAuthorId) || newAuthorId <= 0) {
        alert('Invalid author_id.');
        return;
      }
      try {
        await reassignOrphanLink(bookId, authorId, newAuthorId);
        await this.load();
      } catch (e) {
        alert(this.errorMessage(e) || 'Reassign failed.');
      }
    },
    editAuthor(author: OrphanAuthor) {
      const name = (author.name || '').trim();
      const isHu = !!author.is_hungarian;
      let first = author.first_name || '';
      let last = author.last_name || '';

      if (!first && !last && name) {
        if (name.includes(',')) {
          const parts = name.split(',');
          last = (parts[0] || '').trim();
          first = (parts.slice(1).join(' ') || '').trim();
        } else {
          const parts = name.split(/\s+/).filter(Boolean);
          if (parts.length === 1) {
            if (isHu) {
              last = parts[0] || '';
            } else {
              first = parts[0] || '';
            }
          } else {
            if (isHu) {
              last = parts.shift() || '';
              first = parts.join(' ');
            } else {
              last = parts.pop() || '';
              first = parts.join(' ');
            }
          }
        }
      }

      this.editingAuthorId = author.author_id;
      this.authorDraft = {
        first_name: first,
        last_name: last,
        is_hungarian: isHu,
      };
    },
    cancelEditAuthor() {
      this.editingAuthorId = null;
    },
    async saveAuthor(authorId: number) {
      try {
        await updateOrphanAuthor(authorId, this.authorDraft);
        this.editingAuthorId = null;
        await this.load();
      } catch (e) {
        alert(this.errorMessage(e) || 'Save failed.');
      }
    },
    editPublisher(publisher: OrphanPublisher) {
      this.editingPublisherId = publisher.publisher_id;
      this.publisherDraft = { name: publisher.name || '' };
    },
    cancelEditPublisher() {
      this.editingPublisherId = null;
    },
    async savePublisher(publisherId: number) {
      try {
        await updateOrphanPublisher(publisherId, this.publisherDraft);
        this.editingPublisherId = null;
        await this.load();
      } catch (e) {
        alert(this.errorMessage(e) || 'Save failed.');
      }
    },
  },
};
</script>

<style scoped>
.modal-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,.45); display:flex; align-items:center; justify-content:center; padding: 1rem; z-index: 1000; }
.modal { background: var(--app-bg); border-radius:.75rem; width:min(1400px, 96vw); max-height: 92vh; overflow:auto; box-shadow: 0 14px 44px rgba(0,0,0,.25); border: 1px solid var(--btn-border); }
.modal-header, .modal-footer { display:flex; justify-content:space-between; align-items:center; padding:1rem 1.25rem; border-bottom:1px solid var(--line); }
.modal-footer { border-top:1px solid var(--line); border-bottom:none; }
.modal-body { padding:1rem 1.25rem; display:grid; gap:1.25rem; }
.header-actions { display:flex; align-items:center; gap:.5rem; }

.section h4 { margin: 0 0 .5rem 0; }
.table { width: 100%; border-collapse: collapse; }
.table th, .table td { border-bottom: 1px solid var(--line); padding: .4rem .5rem; text-align: left; }
.table input { width: 100%; padding: .35rem .45rem; border: 1px solid var(--btn-border); border-radius: 6px; font: inherit; background: #fff; }
.actions { display:flex; gap:.4rem; }
.w-actions { width: 10rem; }
.muted { opacity: .7; }

button { padding:.35rem .7rem; border-radius:8px; border:1px solid var(--btn-border); background: var(--btn-bg); cursor:pointer; color: var(--btn-text); }
button.ghost { background: transparent; }
button.danger { background:#c0392b; color:#fff; border-color:#c0392b; }
button:hover { filter: brightness(.98); }
.icon { font-size:1.5rem; line-height:1; background:none; border:none; cursor:pointer; }
</style>
