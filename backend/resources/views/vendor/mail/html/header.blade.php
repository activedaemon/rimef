<!--suppress HtmlDeprecatedAttribute -->
@props(['url'])
{{-- En-tête commun à tous les emails : marque aux trois feuilles et nom du réseau. --}}
<tr>
<td class="header">
<a href="{{ $url }}" class="brand">
<table align="center" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="brand-mark">
<img src="cid:{{ App\Mail\MailBrand::LOGO_CID }}" class="logo" width="44" height="44" alt="">
</td>
<td class="brand-word">
<span class="brand-name">{{ Illuminate\Support\Str::upper(trim($slot)) }}</span>
<span class="brand-tagline">Réseau international<br>des médiatrices francophones</span>
</td>
</tr>
</table>
</a>
</td>
</tr>
