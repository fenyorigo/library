<template>
  <section class="pw-section">
    <header class="pw-header">
      <h4>Change password</h4>
      <div class="muted" v-if="!force">Use a strong password that meets the policy below.</div>
      <div class="force-note" v-else>Admin reset requires you to set a new password.</div>
    </header>

    <div class="pw-grid">
      <label class="field">
        Current password
        <input
          v-model="form.currentPassword"
          type="password"
          autocomplete="current-password"
          :disabled="loading"
        />
      </label>

      <label class="field">
        New password
        <input
          v-model="form.newPassword"
          type="password"
          autocomplete="new-password"
          :disabled="loading"
        />
      </label>

      <label class="field">
        Confirm new password
        <input
          v-model="form.confirmPassword"
          type="password"
          autocomplete="new-password"
          :disabled="loading"
        />
      </label>
    </div>

    <ul class="policy">
      <li :class="{ ok: checks.length }">At least 12 characters</li>
      <li :class="{ ok: checks.lower }">One lowercase letter</li>
      <li :class="{ ok: checks.upper }">One uppercase letter</li>
      <li :class="{ ok: checks.digit }">One digit</li>
      <li :class="{ ok: checks.special }">One special character</li>
      <li :class="{ ok: checks.matches }">Passwords match</li>
    </ul>

    <div class="error" v-if="error">{{ error }}</div>
    <div class="success" v-if="success">{{ success }}</div>

    <div class="actions">
      <button class="primary" @click="submit" :disabled="loading">
        {{ loading ? "Updating..." : "Update password" }}
      </button>
      <button v-if="!force" class="ghost" @click="reset" :disabled="loading">Clear</button>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, reactive, ref } from "vue";
import { changePassword } from "../api";

const emit = defineEmits(["changed"]);

const props = defineProps({
  force: { type: Boolean, default: false },
});

const loading = ref(false);
const error = ref("");
const success = ref("");

const form = reactive({
  currentPassword: "",
  newPassword: "",
  confirmPassword: "",
});

const checks = computed(() => {
  const pwd = form.newPassword || "";
  return {
    length: pwd.length >= 12,
    lower: /[a-z]/.test(pwd),
    upper: /[A-Z]/.test(pwd),
    digit: /[0-9]/.test(pwd),
    special: /[^a-zA-Z0-9]/.test(pwd),
    matches: pwd.length > 0 && pwd === form.confirmPassword,
  };
});

const reset = () => {
  form.currentPassword = "";
  form.newPassword = "";
  form.confirmPassword = "";
  error.value = "";
  success.value = "";
};

const submit = async () => {
  error.value = "";
  success.value = "";
  if (!form.currentPassword || !form.newPassword || !form.confirmPassword) {
    error.value = "Fill out all fields.";
    return;
  }
  if (!checks.value.matches) {
    error.value = "New passwords do not match.";
    return;
  }
  loading.value = true;
  try {
    await changePassword({
      current_password: form.currentPassword,
      new_password: form.newPassword,
    });
    success.value = "Password updated.";
    form.currentPassword = "";
    form.newPassword = "";
    form.confirmPassword = "";
    emit("changed");
  } catch (err) {
    const msg = err instanceof Error ? err.message : "";
    error.value = msg || "Password update failed.";
  } finally {
    loading.value = false;
  }
};
</script>

<style scoped>
.pw-section {
  margin-top: 1.5rem;
  padding-top: 1.2rem;
  border-top: 1px solid #e3e3e3;
}
.pw-header {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  margin-bottom: 0.8rem;
}
.pw-grid {
  display: grid;
  gap: 0.8rem;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  margin-bottom: 0.8rem;
}
.field {
  display: grid;
  gap: 0.35rem;
  font-weight: 600;
}
.field input {
  padding: 0.5rem 0.6rem;
}
.policy {
  list-style: none;
  padding: 0;
  margin: 0 0 0.8rem 0;
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 0.4rem 0.8rem;
}
.policy li {
  font-size: 0.85rem;
  color: #6b6b6b;
}
.policy li.ok {
  color: #1f6f3f;
}
.actions {
  display: flex;
  gap: 0.6rem;
  align-items: center;
}
.error {
  margin-bottom: 0.6rem;
  color: #b3261e;
}
.success {
  margin-bottom: 0.6rem;
  color: #1f6f3f;
}
.ghost {
  background: transparent;
}
.muted {
  opacity: 0.7;
}
.force-note {
  color: #b3261e;
  font-weight: 600;
}
</style>
