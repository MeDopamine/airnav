const puppeteer = require('puppeteer');
const { AxePuppeteer } = require('axe-puppeteer');

(async () => {
  const url = process.env.TEST_URL || 'http://localhost/dashboard/index.php';
  console.log('Launching Chromium and navigating to', url);
  const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
  const page = await browser.newPage();

  try {
    const response = await page.goto(url, { waitUntil: 'networkidle2', timeout: 10000 });
    if (!response || !response.ok()) {
      console.error('Failed to load page. HTTP status:', response && response.status());
      await browser.close();
      process.exitCode = 2;
      return;
    }

    // Wait for the open menu button to appear (if used)
    await page.waitForSelector('#open-menu-btn', { timeout: 5000 });

    // Test 1: axe on initial load
    console.log('Running axe-core on initial page...');
    const resultsInitial = await new AxePuppeteer(page).analyze();
    console.log('Initial violations:', resultsInitial.violations.length);
    resultsInitial.violations.forEach(v => {
      console.log(`- [${v.id}] ${v.help} (${v.nodes.length} nodes)`);
      v.nodes.forEach(n => {
        console.log('  target:', n.target && n.target.join(', '));
      });
    });

    // Try opening mobile menu (simulate mobile viewport)
    await page.setViewport({ width: 375, height: 800 });
    console.log('Clicking mobile open menu button...');
    await page.click('#open-menu-btn');
  // Wait briefly for animation (some Puppeteer builds may not provide waitForTimeout)
  const sleep = (ms) => new Promise(res => setTimeout(res, ms));
  await sleep(400);

    // Run axe again with menu open
    console.log('Running axe-core with mobile menu open...');
    const resultsMenuOpen = await new AxePuppeteer(page).analyze();
    console.log('Menu-open violations:', resultsMenuOpen.violations.length);
    resultsMenuOpen.violations.forEach(v => {
      console.log(`- [${v.id}] ${v.help} (${v.nodes.length} nodes)`);
      v.nodes.forEach(n => {
        console.log('  target:', n.target && n.target.join(', '));
      });
    });

    // Close browser with success
    await browser.close();
    // set non-zero exit if violations found
    if (resultsInitial.violations.length > 0 || resultsMenuOpen.violations.length > 0) {
      console.error('Accessibility violations detected. See output above.');
      process.exitCode = 3;
      return;
    }

    console.log('No accessibility violations detected by axe-core in these checks.');
  } catch (err) {
    console.error('Error running test:', err);
    await browser.close();
    process.exitCode = 1;
  }
})();
