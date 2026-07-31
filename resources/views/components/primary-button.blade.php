<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-cakrawala border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-nusantara focus:bg-nusantara active:bg-nusantara focus:outline-none focus:ring-2 focus:ring-cakrawala focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
