import { test, expect } from "@playwright/test";

const adminUser = process.env.E2E_ADMIN_USER || "";
const adminPass = process.env.E2E_ADMIN_PASS || "";

test.describe("user management", () => {
  test("admin creates a user and user changes password", async ({ page }, testInfo) => {
    testInfo.skip(!adminUser || !adminPass, "E2E admin creds not set");
    const stamp = Date.now();
    const readerUser = `e2e_reader_${stamp}`;
    const readerPass = `TempPass!${stamp}`;
    const newReaderPass = `NewPass!${stamp}`;

    await page.goto("/");
    await login(page, adminUser, adminPass);

    await page.getByRole("button", { name: "Users" }).click();
    await page.getByRole("button", { name: "+ New user" }).click();

    const createModal = page.locator(".submodal .modal-card");
    await createModal.getByLabel("Username").fill(readerUser);
    await createModal.getByLabel("Role").selectOption("reader");
    await createModal.getByLabel("Password").fill(readerPass);
    await createModal.getByLabel("Confirm password").fill(readerPass);
    await createModal.getByRole("button", { name: "Create user" }).click();

    await expect(page.getByRole("cell", { name: readerUser })).toBeVisible();
    await page.getByRole("button", { name: "Close" }).click();

    await page.getByRole("button", { name: "Logout" }).click();

    await login(page, readerUser, readerPass);
    await expect(page.getByRole("button", { name: "Users" })).toHaveCount(0);

    await page.getByRole("button", { name: "Personalize" }).click();
    const pwSection = page.locator(".pw-section");
    await pwSection.getByLabel("Current password").fill(readerPass);
    await pwSection.getByLabel("New password").fill(newReaderPass);
    await pwSection.getByLabel("Confirm new password").fill(newReaderPass);
    await pwSection.getByRole("button", { name: "Update password" }).click();
    await expect(pwSection.getByText("Password updated.")).toBeVisible();
    await page.getByRole("button", { name: "Close" }).click();

    await page.getByRole("button", { name: "Logout" }).click();
    await login(page, readerUser, newReaderPass);
    await expect(page.getByText(`Signed in as ${readerUser}`)).toBeVisible();
  });
});

async function login(page, username, password) {
  await page.getByRole("button", { name: "Sign in" }).click();
  const loginCard = page.locator(".login-card");
  await loginCard.getByLabel("Username").fill(username);
  await loginCard.getByLabel("Password").fill(password);
  await loginCard.getByRole("button", { name: "Sign in" }).click();
  await expect(page.getByText(`Signed in as ${username}`)).toBeVisible();
}
