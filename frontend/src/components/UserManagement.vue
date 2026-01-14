<template>
  <div class="modal-backdrop" @click.self="$emit('close')">
    <div class="modal" role="dialog" aria-modal="true" aria-label="Users">
      <header class="modal-header">
        <h3>Users</h3>
        <div class="header-actions">
          <button class="ghost" @click="load">Refresh</button>
          <button class="icon" @click="$emit('close')" aria-label="Close">×</button>
        </div>
      </header>

      <section class="modal-body">
        <div class="toolbar">
          <button class="primary" @click="openCreate">+ New user</button>
          <span class="muted" v-if="loading">Loading…</span>
        </div>

        <table v-if="rows.length" class="table">
          <thead>
            <tr>
              <th>Username</th>
              <th>Role</th>
              <th>Active</th>
              <th v-if="hasCreatedAt">Created</th>
              <th v-if="hasLastLogin">Last login</th>
              <th class="w-actions">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="u in rows" :key="`u-${u.user_id}`">
              <td>{{ u.username }}</td>
              <td>{{ u.role }}</td>
              <td>{{ u.is_active ? "Yes" : "No" }}</td>
              <td v-if="hasCreatedAt">{{ u.created_at || "—" }}</td>
              <td v-if="hasLastLogin">{{ u.last_login || "—" }}</td>
              <td class="actions">
                <button @click="openEdit(u)">Edit</button>
                <button @click="openReset(u)">Reset password</button>
                <button
                  class="danger"
                  :disabled="isSelf(u)"
                  @click="openDelete(u)"
                >Delete</button>
              </td>
            </tr>
          </tbody>
        </table>
        <div v-else class="muted">No users found.</div>
      </section>

      <footer class="modal-footer">
        <div class="muted small">Admins can create, disable, and reset passwords.</div>
        <button @click="$emit('close')">Close</button>
      </footer>
    </div>

    <div v-if="showCreate" class="submodal" @click.self="closeCreate">
      <div class="modal-card">
        <header>
          <h4>Create user</h4>
        </header>
        <div class="modal-body">
          <label class="field">
            Username
            <input v-model.trim="createDraft.username" type="text" />
          </label>
          <label class="field">
            Role
            <select v-model="createDraft.role">
              <option value="reader">reader</option>
              <option value="admin">admin</option>
            </select>
          </label>
          <label class="field">
            Password
            <input v-model="createDraft.password" type="password" autocomplete="new-password" />
          </label>
          <label class="field">
            Confirm password
            <input v-model="createDraft.confirm" type="password" autocomplete="new-password" />
          </label>
          <ul class="policy">
            <li :class="{ ok: createChecks.length }">At least 12 characters</li>
            <li :class="{ ok: createChecks.lower }">One lowercase letter</li>
            <li :class="{ ok: createChecks.upper }">One uppercase letter</li>
            <li :class="{ ok: createChecks.digit }">One digit</li>
            <li :class="{ ok: createChecks.special }">One special character</li>
            <li :class="{ ok: createChecks.noUsername }">Does not include username</li>
            <li :class="{ ok: createChecks.matches }">Passwords match</li>
          </ul>
          <div class="error" v-if="createError">{{ createError }}</div>
        </div>
        <footer class="modal-footer">
          <button @click="closeCreate">Cancel</button>
          <button class="primary" @click="submitCreate" :disabled="busyCreate">
            {{ busyCreate ? "Creating..." : "Create user" }}
          </button>
        </footer>
      </div>
    </div>

    <div v-if="showEdit" class="submodal" @click.self="closeEdit">
      <div class="modal-card">
        <header>
          <h4>Edit user</h4>
        </header>
        <div class="modal-body">
          <div class="muted">User: {{ editDraft.username }}</div>
          <label class="field">
            Role
            <select v-model="editDraft.role">
              <option value="reader">reader</option>
              <option value="admin">admin</option>
            </select>
          </label>
          <label class="field">
            Active
            <select v-model.number="editDraft.is_active">
              <option :value="1">Active</option>
              <option :value="0">Disabled</option>
            </select>
          </label>
          <div class="error" v-if="editError">{{ editError }}</div>
        </div>
        <footer class="modal-footer">
          <button @click="closeEdit">Cancel</button>
          <button class="primary" @click="submitEdit" :disabled="busyEdit">
            {{ busyEdit ? "Saving..." : "Save changes" }}
          </button>
        </footer>
      </div>
    </div>

    <div v-if="showReset" class="submodal" @click.self="closeReset">
      <div class="modal-card">
        <header>
          <h4>Reset password</h4>
        </header>
        <div class="modal-body">
          <div class="muted">User: {{ resetDraft.username }}</div>
          <label class="field">
            New password
            <input v-model="resetDraft.password" type="password" autocomplete="new-password" />
          </label>
          <label class="field">
            Confirm password
            <input v-model="resetDraft.confirm" type="password" autocomplete="new-password" />
          </label>
          <ul class="policy">
            <li :class="{ ok: resetChecks.length }">At least 12 characters</li>
            <li :class="{ ok: resetChecks.lower }">One lowercase letter</li>
            <li :class="{ ok: resetChecks.upper }">One uppercase letter</li>
            <li :class="{ ok: resetChecks.digit }">One digit</li>
            <li :class="{ ok: resetChecks.special }">One special character</li>
            <li :class="{ ok: resetChecks.noUsername }">Does not include username</li>
            <li :class="{ ok: resetChecks.matches }">Passwords match</li>
          </ul>
          <div class="error" v-if="resetError">{{ resetError }}</div>
        </div>
        <footer class="modal-footer">
          <button @click="closeReset">Cancel</button>
          <button class="primary" @click="submitReset" :disabled="busyReset">
            {{ busyReset ? "Updating..." : "Reset password" }}
          </button>
        </footer>
      </div>
    </div>

    <div v-if="showDelete" class="submodal" @click.self="closeDelete">
      <div class="modal-card">
        <header>
          <h4>Delete user</h4>
        </header>
        <div class="modal-body">
          <p>Delete user <strong>{{ deleteTarget?.username }}</strong>? This cannot be undone.</p>
          <div class="error" v-if="deleteError">{{ deleteError }}</div>
        </div>
        <footer class="modal-footer">
          <button @click="closeDelete">Cancel</button>
          <button class="danger" @click="confirmDelete" :disabled="busyDelete">
            {{ busyDelete ? "Deleting..." : "Delete user" }}
          </button>
        </footer>
      </div>
    </div>
  </div>
</template>

<script lang="ts">
import { adminResetPassword, createUser, deleteUser, listUsers, updateUser } from "../api";

type UserRow = {
  user_id: number;
  username: string;
  role: string;
  is_active: number;
  created_at?: string | null;
  last_login?: string | null;
};

export default {
  name: "UserManagement",
  props: {
    currentUser: { type: Object, default: () => ({}) },
  },
  data() {
    return {
      loading: false,
      rows: [] as UserRow[],
      showCreate: false,
      showEdit: false,
      showReset: false,
      showDelete: false,
      busyCreate: false,
      busyEdit: false,
      busyReset: false,
      busyDelete: false,
      createError: "",
      editError: "",
      resetError: "",
      deleteError: "",
      createDraft: {
        username: "",
        role: "reader",
        password: "",
        confirm: "",
      },
      editDraft: {
        user_id: null as number | null,
        username: "",
        role: "reader",
        is_active: 1,
      },
      resetDraft: {
        user_id: null as number | null,
        username: "",
        password: "",
        confirm: "",
      },
      deleteTarget: null as UserRow | null,
    };
  },
  computed: {
    hasCreatedAt() {
      return this.rows.some((u) => u.created_at !== undefined && u.created_at !== null);
    },
    hasLastLogin() {
      return this.rows.some((u) => u.last_login !== undefined && u.last_login !== null);
    },
    createChecks() {
      return this.checkPolicy(this.createDraft.password, this.createDraft.username, this.createDraft.confirm);
    },
    resetChecks() {
      return this.checkPolicy(this.resetDraft.password, this.resetDraft.username, this.resetDraft.confirm);
    },
  },
  mounted() {
    this.load();
  },
  methods: {
    errorMessage(err: unknown) {
      return err && typeof err === "object" && "message" in err && typeof err.message === "string"
        ? err.message
        : "";
    },
    async load() {
      this.loading = true;
      try {
        const res = await listUsers();
        this.rows = res?.data?.rows || [];
      } catch (err) {
        const msg = this.errorMessage(err);
        alert(msg || "Failed to load users.");
      } finally {
        this.loading = false;
      }
    },
    isSelf(user: UserRow) {
      const current = (this.currentUser?.username || "").toLowerCase();
      return current && current === String(user.username || "").toLowerCase();
    },
    checkPolicy(password: string, username: string, confirm: string) {
      const pwd = password || "";
      const uname = (username || "").toLowerCase();
      return {
        length: pwd.length >= 12,
        lower: /[a-z]/.test(pwd),
        upper: /[A-Z]/.test(pwd),
        digit: /[0-9]/.test(pwd),
        special: /[^a-zA-Z0-9]/.test(pwd),
        noUsername: !uname || !pwd.toLowerCase().includes(uname),
        matches: pwd.length > 0 && pwd === confirm,
      };
    },
    openCreate() {
      this.showCreate = true;
      this.createError = "";
      this.createDraft = {
        username: "",
        role: "reader",
        password: "",
        confirm: "",
      };
    },
    closeCreate() {
      this.showCreate = false;
    },
    async submitCreate() {
      this.createError = "";
      if (!this.createDraft.username || !this.createDraft.password || !this.createDraft.confirm) {
        this.createError = "Fill out all fields.";
        return;
      }
      if (!this.createChecks.matches) {
        this.createError = "Passwords do not match.";
        return;
      }
      this.busyCreate = true;
      try {
        await createUser({
          username: this.createDraft.username,
          role: this.createDraft.role,
          password: this.createDraft.password,
        });
        await this.load();
        this.closeCreate();
      } catch (err) {
        this.createError = this.errorMessage(err) || "Create user failed.";
      } finally {
        this.busyCreate = false;
      }
    },
    openEdit(user: UserRow) {
      this.showEdit = true;
      this.editError = "";
      this.editDraft = {
        user_id: user.user_id,
        username: user.username,
        role: user.role,
        is_active: user.is_active ? 1 : 0,
      };
    },
    closeEdit() {
      this.showEdit = false;
    },
    async submitEdit() {
      this.editError = "";
      this.busyEdit = true;
      try {
        await updateUser({
          user_id: this.editDraft.user_id,
          role: this.editDraft.role,
          is_active: this.editDraft.is_active,
        });
        await this.load();
        this.closeEdit();
      } catch (err) {
        this.editError = this.errorMessage(err) || "Update failed.";
      } finally {
        this.busyEdit = false;
      }
    },
    openReset(user: UserRow) {
      this.showReset = true;
      this.resetError = "";
      this.resetDraft = {
        user_id: user.user_id,
        username: user.username,
        password: "",
        confirm: "",
      };
    },
    closeReset() {
      this.showReset = false;
    },
    async submitReset() {
      this.resetError = "";
      if (!this.resetDraft.password || !this.resetDraft.confirm) {
        this.resetError = "Fill out all fields.";
        return;
      }
      if (!this.resetChecks.matches) {
        this.resetError = "Passwords do not match.";
        return;
      }
      this.busyReset = true;
      try {
        await adminResetPassword({
          user_id: this.resetDraft.user_id,
          newPassword: this.resetDraft.password,
        });
        this.closeReset();
      } catch (err) {
        this.resetError = this.errorMessage(err) || "Reset failed.";
      } finally {
        this.busyReset = false;
      }
    },
    openDelete(user: UserRow) {
      this.showDelete = true;
      this.deleteError = "";
      this.deleteTarget = user;
    },
    closeDelete() {
      this.showDelete = false;
      this.deleteTarget = null;
    },
    async confirmDelete() {
      if (!this.deleteTarget) return;
      this.busyDelete = true;
      this.deleteError = "";
      try {
        await deleteUser(this.deleteTarget.user_id);
        await this.load();
        this.closeDelete();
      } catch (err) {
        this.deleteError = this.errorMessage(err) || "Delete failed.";
      } finally {
        this.busyDelete = false;
      }
    },
  },
};
</script>

<style scoped>
.toolbar {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  margin-bottom: 0.8rem;
}
.table {
  width: 100%;
  border-collapse: collapse;
}
.table th,
.table td {
  padding: 0.5rem 0.6rem;
  border-bottom: 1px solid #e3e3e3;
  text-align: left;
}
.w-actions {
  width: 220px;
}
.actions {
  display: flex;
  gap: 0.4rem;
  flex-wrap: wrap;
}
.muted {
  opacity: 0.7;
}
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 40;
}
.modal {
  width: min(1600px, 92vw);
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2);
  display: flex;
  flex-direction: column;
  max-height: 90vh;
}
.modal-header,
.modal-footer {
  padding: 0.8rem 1rem;
  border-bottom: 1px solid #eee;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.modal-footer {
  border-top: 1px solid #eee;
  border-bottom: none;
}
.modal-body {
  padding: 1rem;
  overflow: auto;
}
.header-actions {
  display: flex;
  gap: 0.5rem;
}
.icon {
  font-size: 1.2rem;
  line-height: 1;
}
.submodal {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 50;
}
.modal-card {
  width: min(520px, 92vw);
  background: #fff;
  border-radius: 10px;
  box-shadow: 0 12px 40px rgba(0, 0, 0, 0.25);
  display: flex;
  flex-direction: column;
}
.modal-card header {
  padding: 0.8rem 1rem;
  border-bottom: 1px solid #eee;
}
.field {
  display: grid;
  gap: 0.35rem;
  margin-bottom: 0.8rem;
  font-weight: 600;
}
.field input,
.field select {
  padding: 0.5rem 0.6rem;
}
.policy {
  list-style: none;
  padding: 0;
  margin: 0 0 0.6rem 0;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 0.35rem 0.6rem;
}
.policy li {
  font-size: 0.85rem;
  color: #6b6b6b;
}
.policy li.ok {
  color: #1f6f3f;
}
.error {
  color: #b3261e;
}
.danger {
  background: #b3261e;
  color: #fff;
}
.ghost {
  background: transparent;
}
.primary {
  background: #1f6f3f;
  color: #fff;
}
</style>
