@props(['pribadi' => true])

{{-- Latar foto rumah di belakang tampilan kaca. "pribadi" = boleh memakai foto unggahan super admin (hanya untuk pengguna yang login). --}}
@php
    $foto = $pribadi && \App\Models\Pengaturan::ambil('latar_dashboard')
        ? route('latar', ['v' => substr(md5(\App\Models\Pengaturan::ambil('latar_dashboard')), 0, 8)])
        : asset('img/latar-rumah.jpg');
@endphp
<div class="fixed inset-0 -z-10 bg-cover bg-center saturate-[.85]" style="background-image: url('{{ $foto }}')" aria-hidden="true"></div>
<div class="fixed inset-0 -z-10 bg-gradient-to-br from-[#f3ede6]/35 via-transparent to-[#18222f]/25" aria-hidden="true"></div>
