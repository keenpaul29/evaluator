import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const fixtures = JSON.parse(
  fs.readFileSync(path.join(process.cwd(), 'e2e', 'fixtures.json'), 'utf8'),
);

const assignmentUrl = (token) => `/assignments/${token}`;
const candidateUrl = (id) => `/candidates/${id}`;

test.describe('take-home assignment — candidate facing', () => {
  test('dispatched assignment renders the full brief', async ({ page }) => {
    await page.goto(assignmentUrl(fixtures.dispatched.token));

    await expect(page.getByRole('heading', { name: fixtures.dispatched.title })).toBeVisible();
    await expect(page.getByText(fixtures.dispatched.candidate)).toBeVisible();
    await expect(page.getByText(fixtures.dispatched.objective)).toBeVisible();
    await expect(page.getByText('Deliverables')).toBeVisible();
    await expect(page.getByText('What we will look at')).toBeVisible();
    await expect(page.getByText('Timebox')).toBeVisible();
    await expect(page.getByText('Using AI tools')).toBeVisible();
  });

  test('submission form is visible when dispatched', async ({ page }) => {
    await page.goto(assignmentUrl(fixtures.dispatched.token));

    await expect(page.getByLabel('Repository URL')).toBeVisible();
    await expect(page.getByLabel('Reflection')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Submit assignment' })).toBeVisible();
  });

  test('native browser validation blocks a malformed repository url', async ({ page }) => {
    await page.goto(assignmentUrl(fixtures.dispatched.token));

    await page.getByLabel('Repository URL').fill('not-a-url');
    await page.getByLabel('Reflection').fill('Too short.');
    await page.getByRole('button', { name: 'Submit assignment' }).click();

    const valid = await page.getByLabel('Repository URL').evaluate((el) => el.checkValidity());
    expect(valid).toBe(false);
  });

  test('server rejects a too-short reflection', async ({ page }) => {
    await page.goto(assignmentUrl(fixtures.dispatched.token));

    await page.getByLabel('Repository URL').fill('https://github.com/priya-e2e/take-home');
    await page.getByLabel('Reflection').fill('Too short.');
    await page.getByRole('button', { name: 'Submit assignment' }).click();

    await expect(page.getByText('The reflection field must be at least 50 characters.')).toBeVisible();
  });

  test('pre-dispatch assignment link is not accessible', async ({ page }) => {
    const response = await page.goto(assignmentUrl(fixtures.generated.token));
    expect(response?.status()).toBe(404);
  });

  test('unknown token returns 404', async ({ page }) => {
    const response = await page.goto('/assignments/does-not-exist-token');
    expect(response?.status()).toBe(404);
  });
});

test.describe('take-home assignment — HR facing', () => {
  test('dispatched candidate page shows assignment card and submission link', async ({ page }) => {
    await page.goto(candidateUrl(fixtures.dispatched.candidate_id));

    await expect(page.getByText('Take-Home Assignment')).toBeVisible();
    await expect(page.getByText('Dispatched')).toBeVisible();
    await expect(
      page.getByRole('link', { name: assignmentUrl(fixtures.dispatched.token) }),
    ).toBeVisible();
  });

  test('generated assignment exposes a dispatch button', async ({ page }) => {
    await page.goto(candidateUrl(fixtures.generated.candidate_id));

    await expect(page.getByText('Take-Home Assignment')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Dispatch' })).toBeVisible();
  });
});

test.describe('take-home assignment — dispatch and submit journey', () => {
  test('HR dispatches, then candidate submits through the browser', async ({ page }) => {
    await page.goto(candidateUrl(fixtures.generated.candidate_id));
    await page.getByRole('button', { name: 'Dispatch' }).click();
    await expect(page.getByText('Take-home assignment dispatched to candidate.')).toBeVisible();

    const response = await page.goto(assignmentUrl(fixtures.generated.token));
    expect(response?.status()).toBe(200);

    await page.getByLabel('Repository URL').fill('https://github.com/arjun-e2e/take-home');
    await page
      .getByLabel('Reflection')
      .fill(
        'I started by profiling the slow query, then added caching behind a feature flag ' +
          'so the export job could roll out safely. Tests cover both paths.',
      );
    await page.getByRole('button', { name: 'Submit assignment' }).click();

    await expect(
      page.getByText('Assignment submitted. Our team will review it.').first(),
    ).toBeVisible();

    await page.goto(candidateUrl(fixtures.generated.candidate_id));
    await expect(page.getByText('Submitted').first()).toBeVisible();
    await expect(page.getByText('https://github.com/arjun-e2e/take-home')).toBeVisible();
  });
});
