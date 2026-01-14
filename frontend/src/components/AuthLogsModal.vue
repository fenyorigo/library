<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Auth logs">
      <header class="modal-header">
        <h3>Auth logs</h3>
        <div class="header-actions">
          <button class="ghost" @click="load">Refresh</button>
          <button class="icon" @click="$emit('close')" aria-label="Close">x</button>
        </div>
      </header>

      <section class="modal-body">
        <div class="toolbar">
          <input
            v-model.trim="q"
            type="search"
            placeholder="Search username, event, or IP..."
            @keyup.enter="applyFilters"
          />
          <select v-model="eventType">
            <option value="">All events</option>
            <option value="login_success">login_success</option>
            <option value="login_failed">login_failed</option>
            <option value="logout">logout</option>
            <option value="password_change">password_change</option>
            <option value="admin_reset_password">admin_reset_password</option>
            <option value="role_change">role_change</option>
            <option value="user_enabled">user_enabled</option>
            <option value="user_disabled">user_disabled</option>
          </select>
          <button @click="applyFilters">Filter</button>
          <button class="ghost" @click="clearFilters">Clear</button>
          <span class="muted" v-if="loading">Loading...</span>
        </div>

        <table v-if="rows.length" class="table logs">
          <thead>
            <tr>
              <th class="w-time">Time</th>
              <th class="w-event">Event</th>
              <th class="w-user">User</th>
              <th class="w-ip">IP</th>
              <th class="w-details">Details</th>
              <th class="w-agent">User agent</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id">
              <td class="mono">{{ formatTime(row.created_at) }}</td>
              <td>
                <span class="pill">{{ row.event_type }}</span>
              </td>
              <td>
                <div class="user-cell">
                  <div>{{ row.username_snapshot || "-" }}</div>
                  <div class="muted small">ID: {{ row.user_id ?? "-" }}</div>
                </div>
              </td>
              <td class="mono">{{ row.ip_address || "-" }}</td>
              <td class="details">{{ formatDetails(row.details) }}</td>
              <td class="agent" :title="row.user_agent || ''">
                {{ truncate(row.user_agent, 72) || "-" }}
              </td>
            </tr>
          </tbody>
        </table>

        <div v-else class="muted">No events found.</div>
      </section>

      <footer class="modal-footer">
        <div class="pager">
          <button :disabled="page <= 1" @click="goPage(page - 1)">Prev</button>
          <span class="muted">
            Page {{ page }} of {{ pageCount }} | {{ total }} events
          </span>
          <button :disabled="page >= pageCount" @click="goPage(page + 1)">Next</button>
        </div>
        <div class="purge-actions">
          <button class="ghost" :disabled="purgeBusy" @click="purgeOlderThan(6)">
            {{ purgeBusy ? "Purging..." : "Clear >6 months" }}
          </button>
          <button class="ghost" :disabled="purgeBusy" @click="purgeOlderThan(12)">
            {{ purgeBusy ? "Purging..." : "Clear >12 months" }}
          </button>
        </div>
        <div class="per-page">
          <label>
            Per page
            <select v-model.number="perPage">
              <option :value="25">25</option>
              <option :value="50">50</option>
              <option :value="100">100</option>
              <option :value="200">200</option>
            </select>
          </label>
        </div>
        <button @click="$emit('close')">Close</button>
      </footer>
    </div>
  </div>
</template>

<script lang="ts">
import { listAuthEvents, purgeAuthEvents } from "../api";

type AuthEventRow = {
  id: number;
  user_id: number | null;
  username_snapshot: string;
  event_type: string;
  ip_address: string;
  user_agent: string | null;
  details: Record<string, unknown> | null;
  created_at: string;
};

export default {
  name: "AuthLogsModal",
  data() {
    return {
      loading: false,
      rows: [] as AuthEventRow[],
      page: 1,
      perPage: 50,
      total: 0,
      q: "",
      eventType: "",
      purgeBusy: false,
    };
  },
  computed: {
    pageCount(): number {
      return Math.max(1, Math.ceil(this.total / this.perPage));
    },
  },
  watch: {
    perPage() {
      this.page = 1;
      this.load();
    },
  },
  mounted() {
    this.load();
  },
  methods: {
    async load() {
      this.loading = true;
      try {
        const res = await listAuthEvents({
          page: this.page,
          per: this.perPage,
          q: this.q || undefined,
          event_type: this.eventType || undefined,
        });
        this.rows = res?.data?.rows || [];
        this.total = Number.isFinite(res?.data?.meta?.total) ? res.data.meta.total : 0;
      } catch (err) {
        alert("Failed to load auth logs.");
      } finally {
        this.loading = false;
      }
    },
    applyFilters() {
      this.page = 1;
      this.load();
    },
    clearFilters() {
      this.q = "";
      this.eventType = "";
      this.page = 1;
      this.load();
    },
    async purgeOlderThan(months: number) {
      if (this.purgeBusy) return;
      const label = months === 6 ? "6 months" : "12 months";
      if (!confirm(`Delete auth events older than ${label}?`)) return;
      this.purgeBusy = true;
      try {
        const res = await purgeAuthEvents(months);
        const deleted = res?.data?.deleted ?? 0;
        alert(`Deleted ${deleted} events older than ${label}.`);
        this.page = 1;
        this.load();
      } catch (err) {
        alert("Purge failed.");
      } finally {
        this.purgeBusy = false;
      }
    },
    goPage(next: number) {
      if (next < 1 || next > this.pageCount) return;
      this.page = next;
      this.load();
    },
    formatTime(ts: string) {
      if (!ts) return "-";
      const normalized = ts.includes("T") ? ts : ts.replace(" ", "T") + "Z";
      const date = new Date(normalized);
      if (Number.isNaN(date.getTime())) return ts;
      return date.toLocaleString();
    },
    formatDetails(details: Record<string, unknown> | null) {
      if (!details) return "-";
      const parts: string[] = [];
      if (typeof details.reason === "string") parts.push(`reason:${details.reason}`);
      if (typeof details.actor_username === "string") parts.push(`actor:${details.actor_username}`);
      if (typeof details.previous_role === "string" && typeof details.new_role === "string") {
        parts.push(`role:${details.previous_role}->${details.new_role}`);
      }
      if (typeof details.previous_active === "number" && typeof details.new_active === "number") {
        const prev = details.previous_active ? "active" : "disabled";
        const next = details.new_active ? "active" : "disabled";
        parts.push(`status:${prev}->${next}`);
      }
      if (parts.length) return parts.join(" | ");
      try {
        return JSON.stringify(details);
      } catch (e) {
        return "-";
      }
    },
    truncate(value: string | null, max: number) {
      if (!value) return "";
      const text = String(value);
      if (text.length <= max) return text;
      return text.slice(0, max - 3) + "...";
    },
  },
};
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1rem;
  z-index: 1000;
}
.modal {
  background: var(--app-bg);
  border-radius: 0.75rem;
  width: min(1500px, 96vw);
  max-height: 92vh;
  overflow: auto;
  box-shadow: 0 14px 44px rgba(0, 0, 0, 0.25);
  border: 1px solid var(--btn-border);
  display: flex;
  flex-direction: column;
}
.modal-header,
.modal-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 1rem 1.25rem;
  border-bottom: 1px solid var(--line);
  gap: 1rem;
}
.modal-footer {
  border-top: 1px solid var(--line);
  border-bottom: none;
  flex-wrap: wrap;
}
.modal-body {
  padding: 1rem 1.25rem;
  display: grid;
  gap: 1rem;
}
.header-actions {
  display: flex;
  gap: 0.5rem;
}
.toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  align-items: center;
}
.toolbar input {
  min-width: 240px;
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th,
.table td {
  padding: 0.5rem 0.6rem;
  border-bottom: 1px solid var(--line);
  text-align: left;
  vertical-align: top;
}
.w-time {
  width: 150px;
}
.w-event {
  width: 140px;
}
.w-user {
  width: 180px;
}
.w-ip {
  width: 140px;
}
.w-details {
  width: 220px;
}
.w-agent {
  width: 240px;
}
.pill {
  display: inline-flex;
  padding: 0.1rem 0.45rem;
  border-radius: 999px;
  border: 1px solid var(--btn-border);
  background: var(--btn-bg);
  font-size: 0.85rem;
}
.muted {
  opacity: 0.7;
}
.small {
  font-size: 0.85rem;
}
.mono {
  font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
}
.details {
  color: var(--app-fg);
}
.agent {
  word-break: break-word;
  max-width: 320px;
}
.user-cell {
  display: grid;
  gap: 0.15rem;
}
.pager {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  flex-wrap: wrap;
}
.purge-actions {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  flex-wrap: wrap;
}
.per-page select {
  margin-left: 0.4rem;
}
</style>
