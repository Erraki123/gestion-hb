
@php
    $web = $web ?? false;
    $logoPath = public_path('images/logo.png');
    $logoSrc = $web
        ? asset('images/logo.png')
        : $logoPath;
@endphp

<div class="premium-topline"></div>

<div class="premium-header">
    <table>
        <tr>
            <td style="width:32%;">
                @if(file_exists($logoPath) || $web)
                    <img src="{{ $logoSrc }}"
                         class="premium-logo"
                         alt="HN BLOOM">
                @else
                    <div class="premium-brand">
                        HN BLOOM
                    </div>
                @endif
            </td>

            <td style="width:28%; text-align:center;">
                <div class="premium-slogan">
                    INNOVATION<br>
                    CONFIANCE<br>
                    EXCELLENCE
                </div>
            </td>

            <td style="width:40%; text-align:right;">
                <div class="premium-title">
                    {{ $title }}
                </div>

                <div class="premium-ref">
                    {{ $ref }}
                </div>

                <div class="premium-date">
                    {{ $date }}
                </div>
            </td>
        </tr>
    </table>
</div>
