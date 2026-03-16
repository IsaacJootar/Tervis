Add-Type -AssemblyName System.Drawing

$assets = 'C:\xampp\htdocs\app1\public\assets'
if (-not (Test-Path $assets)) { New-Item -ItemType Directory -Path $assets | Out-Null }

$white = [System.Drawing.Color]::White
$wordFill = [System.Drawing.ColorTranslator]::FromHtml('#153E74')

function New-RoundedRectPath {
    param([float]$x,[float]$y,[float]$w,[float]$h,[float]$r)
    $path = New-Object System.Drawing.Drawing2D.GraphicsPath
    $d = $r * 2
    $path.AddArc($x, $y, $d, $d, 180, 90)
    $path.AddArc($x + $w - $d, $y, $d, $d, 270, 90)
    $path.AddArc($x + $w - $d, $y + $h - $d, $d, $d, 0, 90)
    $path.AddArc($x, $y + $h - $d, $d, $d, 90, 90)
    $path.CloseFigure()
    return $path
}

function Draw-C19Icon {
    param(
        [System.Drawing.Graphics]$g,
        [float]$x,
        [float]$y,
        [float]$size,
        [System.Drawing.Color]$color
    )

    $s = $size / 48.0
    $pen = New-Object System.Drawing.Pen($color, [float](1.9 * $s))
    $pen.StartCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.EndCap = [System.Drawing.Drawing2D.LineCap]::Round
    $pen.LineJoin = [System.Drawing.Drawing2D.LineJoin]::Round

    $brush = New-Object System.Drawing.SolidBrush($color)

    $nodes = @(
        @{x=10.8; y=16.6},
        @{x=24.0; y=10.8},
        @{x=37.2; y=16.6},
        @{x=10.8; y=31.4},
        @{x=24.0; y=37.2},
        @{x=37.2; y=31.4}
    )

    foreach ($n in $nodes) {
        $r = 2.0 * $s
        $g.FillEllipse($brush, [float]($x + $n.x*$s - $r), [float]($y + $n.y*$s - $r), [float](2*$r), [float](2*$r))
    }

    $ptsTop = [System.Drawing.PointF[]]@(
        [System.Drawing.PointF]::new([float]($x + 12.6*$s), [float]($y + 17.4*$s)),
        [System.Drawing.PointF]::new([float]($x + 22.2*$s), [float]($y + 13.2*$s)),
        [System.Drawing.PointF]::new([float]($x + 31.8*$s), [float]($y + 17.4*$s))
    )
    $g.DrawLines($pen, $ptsTop)

    $ptsBottom = [System.Drawing.PointF[]]@(
        [System.Drawing.PointF]::new([float]($x + 12.6*$s), [float]($y + 30.6*$s)),
        [System.Drawing.PointF]::new([float]($x + 22.2*$s), [float]($y + 34.8*$s)),
        [System.Drawing.PointF]::new([float]($x + 31.8*$s), [float]($y + 30.6*$s))
    )
    $g.DrawLines($pen, $ptsBottom)

    $g.DrawLine($pen, [float]($x + 10.8*$s), [float]($y + 18.6*$s), [float]($x + 10.8*$s), [float]($y + 29.4*$s))
    $g.DrawLine($pen, [float]($x + 37.2*$s), [float]($y + 18.6*$s), [float]($x + 37.2*$s), [float]($y + 29.4*$s))

    $brush.Dispose()
    $pen.Dispose()
}

function New-C19ShadeExport {
    param(
        [string]$fileName,
        [string]$c1,
        [string]$c2
    )

    $w = 1800
    $h = 560

    $bmp = New-Object System.Drawing.Bitmap($w, $h, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
    $g = [System.Drawing.Graphics]::FromImage($bmp)
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias
    $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
    $g.TextRenderingHint = [System.Drawing.Text.TextRenderingHint]::ClearTypeGridFit
    $g.Clear([System.Drawing.Color]::Transparent)

    $markRect = [System.Drawing.RectangleF]::new(120, 130, 300, 300)
    $markPath = New-RoundedRectPath -x $markRect.X -y $markRect.Y -w $markRect.Width -h $markRect.Height -r 46
    $c1Color = [System.Drawing.ColorTranslator]::FromHtml($c1)
    $c2Color = [System.Drawing.ColorTranslator]::FromHtml($c2)
    $markBrush = New-Object System.Drawing.Drawing2D.LinearGradientBrush($markRect, $c1Color, $c2Color, 135)
    $g.FillPath($markBrush, $markPath)
    $markBrush.Dispose()

    $markBorder = New-Object System.Drawing.Pen([System.Drawing.Color]::FromArgb(70,255,255,255), 2)
    $g.DrawPath($markBorder, $markPath)
    $markBorder.Dispose()

    Draw-C19Icon -g $g -x 198 -y 208 -size 144 -color $white

    # Draw outlined wordmark for better contrast on mixed backgrounds.
    $wordFont = New-Object System.Drawing.Font('Segoe UI', 208, [System.Drawing.FontStyle]::Bold, [System.Drawing.GraphicsUnit]::Pixel)
    $outlineBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(210,255,255,255))
    $wordBrush = New-Object System.Drawing.SolidBrush($wordFill)
    $wordX = 458
    $wordY = 152
    foreach ($dy in -4..4) {
        foreach ($dx in -4..4) {
            if ($dx -ne 0 -or $dy -ne 0) {
                if ([Math]::Abs($dx) + [Math]::Abs($dy) -le 5) {
                    $g.DrawString('cureva', $wordFont, $outlineBrush, [float]($wordX + $dx), [float]($wordY + $dy))
                }
            }
        }
    }
    $g.DrawString('cureva', $wordFont, $wordBrush, $wordX, $wordY)

    $out = Join-Path $assets $fileName
    $bmp.Save($out, [System.Drawing.Imaging.ImageFormat]::Png)

    $outlineBrush.Dispose()
    $wordBrush.Dispose()
    $wordFont.Dispose()
    $markPath.Dispose()
    $g.Dispose()
    $bmp.Dispose()
}

New-C19ShadeExport 'cureva-c19-blue-1.png'  '#9AD1FF' '#3B77DC'
New-C19ShadeExport 'cureva-c19-blue-2.png'  '#82B8FF' '#2D63C7'
New-C19ShadeExport 'cureva-c19-blue-3.png'  '#7FA8FF' '#274FB3'
New-C19ShadeExport 'cureva-c19-green-1.png' '#9BE8CF' '#2F9D77'
New-C19ShadeExport 'cureva-c19-green-2.png' '#7ADFBB' '#1D8A64'
New-C19ShadeExport 'cureva-c19-green-3.png' '#69D3AD' '#1A7759'
