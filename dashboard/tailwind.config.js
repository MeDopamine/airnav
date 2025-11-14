module.exports = {
  content: [
    // include PHP files in dashboard and parent (project root) so pages like /login.php are scanned
    "../*.php",
    "../**/*.php",
    "./*.php",
    "./**/*.php",
    "./assets/js/*.js"
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
