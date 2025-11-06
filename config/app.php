
<section id="installBanner" class="bg-gradient-to-r from-gray-800 via-gray-900 to-gray-800 text-white rounded-2xl shadow-2xl p-6 flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0 md:space-x-6 transition-all duration-700">
  <div class="flex items-center flex-1 space-x-4">

    <img src="assests/apple-touch-icon.png" alt="AmarWorld App" class="w-16 h-16 rounded-lg shadow-md">

    <div>
      <h3 class="text-lg md:text-xl font-bold">Install AmarWorld</h3>
      <p class="text-sm md:text-base text-gray-300 mt-1">Fast, smooth & never miss updates!</p>
    </div>
  </div>

  <div class="flex items-center space-x-3">

    <button id="installBtn" class="flex items-center gap-2 bg-white text-gray-900 font-semibold px-5 py-2 rounded-xl shadow-lg hover:scale-105 hover:shadow-2xl transition transform">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5-5m0 0l5 5m-5-5v12" />
      </svg>
      Install
    </button>


    <button id="closeBanner" class="text-gray-400 hover:text-gray-200 transition transform scale-100 hover:scale-110">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </button>
  </div>
</section>

<script>
let deferredPrompt;
const installBtn = document.getElementById('installBtn');
const banner = document.getElementById('installBanner');
const closeBtn = document.getElementById('closeBanner');


banner.classList.remove('opacity-0', 'translate-y-10');


window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
});


installBtn.addEventListener('click', async () => {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    deferredPrompt = null;
    if (outcome === 'accepted') {
      banner.classList.add('hidden');
      localStorage.setItem('bannerClosed', 'true');
    }
  } else {
    alert("Your browser does not support 'Add to Home Screen'. Use mobile Chrome on HTTPS.");
  }
});


closeBtn.addEventListener('click', () => {
  banner.classList.add('hidden');
  localStorage.setItem('bannerClosed', 'true');
});
</script>
