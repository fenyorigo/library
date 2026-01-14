<template>
  <main class="wrap">
    <!-- tiny search + status -->
    <div class="topbar">
      <input
        v-model="q"
        type="search"
        placeholder="Search title/author…"
        @keyup.enter="onSearch"
      />
      <button :disabled="loading" @click="onSearch">Search</button>
      <span v-if="loading" class="muted">Loading…</span>
    </div>

    <BookList
      :rows="rows"
      :total="total"
      :page="page"
      :per-page="perPage"
      :sort="sort"
      :dir="dir"
      :loading="loading"
      :q="q"
      @change-page="onChangePage"
      @change-per-page="onChangePerPage"
      @change-sort="onChangeSort"
      @view="onView"
      @edit="onEdit"
      @delete="onDelete"
    />
  </main>
</template>

<script lang="js">
import BookList from '@/components/BookList.vue';

export default {
  name: 'HomePage',
  components: { BookList },
  data() {
    return {
      rows: [],
      total: 0,
      page: 1,
      perPage: 25,
      sort: 'id',   // maps to list_books.php order
      dir: 'desc',
      q: '',
      loading: false,
    };
  },
  mounted() { this.load(); },
  methods: {
    async load() {
      this.loading = true;
      try {
        const params = new URLSearchParams({
          page: String(this.page),
          per: String(this.perPage),
          sort: this.sort,
          dir: this.dir,
        });
        if (this.q) params.set('q', this.q);

        const res  = await fetch(`/list_books.php?${params.toString()}`, {
          credentials: 'same-origin',
        });
        const data = await res.json();
        if (!res.ok || (data && data.ok === false)) {
          throw new Error(data && data.error ? data.error : `HTTP ${res.status}`);
        }
        const payload = data && data.data ? data.data : [];
        const meta = data && data.meta ? data.meta : {};

        this.rows  = Array.isArray(payload) ? payload : [];
        this.total = Number(meta.total ?? 0);
      } catch (e) {
        console.error('Load books failed', e);
        this.rows = [];
        this.total = 0;
      } finally {
        this.loading = false;
      }
    },
    onChangePage(newPage) {
      this.page = Number(newPage);
      this.load();
    },
    onChangePerPage(val) {
      this.perPage = Number(val);
      this.page = 1;
      this.load();
    },
    onChangeSort({ sort, dir }) {
      this.sort = sort;
      this.dir = dir;
      this.page = 1;
      this.load();
    },
    onSearch() {
      this.page = 1;
      this.load();
    },
    onView(b)  { console.log('view', b); },
    onEdit(b)  { console.log('edit', b); },
    onDelete(id) {
      // example: fetch(`/delete_book.php?id=${id}`, { method:'POST' }).then(() => this.load());
      console.log('delete', id);
    },
  },
};
</script>

<style scoped>
.wrap { max-width: 1100px; margin: 0 auto; padding: 1rem; }
.topbar { display: flex; gap: .5rem; align-items: center; margin-bottom: .75rem; }
.topbar input[type="search"] { flex: 1; padding: .5rem .6rem; }
.muted { opacity: .7; }
</style>
