<!doctype html>
<html>
	<head>
		<meta charset="utf-8">
		<title>K-Builder</title>
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=yes">
		<link rel="icon" type="image/gif" href="assets/images/favicon.png">
		<link rel="alternate" href="https://karleton.com.mx" hreflang="en" />

        <link rel="stylesheet" type="text/css" href="assets/css/fonts.css">
        <link rel="stylesheet" type="text/css" href="assets/css/style.css">
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>
        <script type="text/javascript" src="assets/js/script.js"></script>
	</head>
	<body id="body">
        <header>
            <div class="logo">
                <a href="https://karleton.com.mx"><img src="assets/images/kbuilder-logo.svg" alt="K-Builder"></a>
            </div>
        </header>
        <main>
            <div class="kbuilder house-style-1" id="kbuilder">
                <div class="color-selector-container" id="color-selector-container">
                    <div class="color-selector-nav">
                        <button class="color-selector-nav-button roof" onClick="showColorSelector('roof');">
                            <svg id="nav-button-roof" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 68 63">
                            <rect class="bg" x="0" y="0" width="68" height="63"/>
                            <path class="icon" d="M58.89,44.89l-9.76-25.04c-.17-.44-.59-.72-1.06-.72h-9.38v-4.61c0-.63-.51-1.14-1.14-1.14h-4.15c-.63,0-1.14.51-1.14,1.14v4.61h-12.75c-.38,0-.73.19-.94.5-.05.07-.09.15-.12.23l-9.76,25.04c-.11.28-.1.59.02.87.12.28.35.49.63.6.13.05.27.08.41.08.47,0,.89-.28,1.06-.72l8.7-22.32,8.7,22.32c.17.44.59.72,1.06.72h28.55c.38,0,.73-.19.94-.5.21-.31.26-.7.12-1.05ZM35.05,40.18l1.55,3.99h-6.55l-1.55-3.99h6.55ZM32.61,33.92l1.55,3.99h-6.55l-1.55-3.99h6.55ZM30.17,27.66l1.55,3.99h-6.55l-1.55-3.99h6.55ZM39.04,44.17l-8.87-22.76h2.09v2.22c0,.63.51,1.14,1.14,1.14s1.14-.51,1.14-1.14v-7.97h1.88v7.97c0,.63.51,1.14,1.14,1.14s1.14-.51,1.14-1.14v-2.22h8.6l8.87,22.76h-17.12ZM29.28,25.39h-6.55l-1.55-3.99h6.55l1.55,3.99Z"/>
                            </svg>
                        </button>
                        <button class="color-selector-nav-button walls" onClick="showColorSelector('walls');">
                            <svg id="nav-button-walls" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 68 63">
                            <rect class="bg" x="0" y="0" width="68" height="63"/>
                            <path class="icon" d="M50.62,27.05h-7.1v-5.39c0-.8-.65-1.45-1.45-1.45h-7.1v-5.39c0-.8-.65-1.45-1.45-1.45h-17.11c-.8,0-1.45.65-1.45,1.45v34.22c0,.8.65,1.45,1.45,1.45h34.22c.8,0,1.45-.65,1.45-1.45v-20.53c0-.8-.65-1.45-1.45-1.45ZM49.16,29.96v3.94h-14.2v-3.94h14.2ZM17.85,20.21v-3.94h14.2v3.94h-14.2ZM26.41,27.05v-3.94h14.2v3.94h-14.2ZM17.85,27.05v-3.94h5.65v3.94h-5.65ZM49.16,43.64v3.94h-14.2v-3.94h14.2ZM40.61,36.8v3.94h-14.2v-3.94h14.2ZM17.85,33.89v-3.94h14.2v3.94h-14.2ZM17.85,40.74v-3.94h5.65v3.94h-5.65ZM32.05,43.64v3.94h-14.2v-3.94h14.2ZM49.16,36.8v3.94h-5.65v-3.94h5.65Z"/>
                            </svg>                            
                        </button>
                        <button class="color-selector-nav-button windows" onClick="showColorSelector('windows');">
                            <svg id="nav-button-walls" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 68 63">
                                <rect class="bg" x="0" y="0" width="68" height="63"/>
                                <path class="icon" d="M48.16,46.33h-.14V13.71c0-.46-.38-.84-.84-.84h-28.34c-.46,0-.84.38-.84.84v32.62h-.14c-1.44,0-2.61,1.17-2.61,2.61v2.17c0,.46.38.84.84.84h33.85c.46,0,.84-.38.84-.84v-2.17c0-1.44-1.17-2.61-2.61-2.61ZM19.68,46.33V14.55h26.66v31.78h-26.66ZM49.09,48.94v1.33H16.92v-1.33c0-.51.42-.93.93-.93h30.31c.51,0,.93.42.93.93ZM44.03,16.02h-22.04c-.46,0-.84.38-.84.84v27.16c0,.46.38.84.84.84h22.04c.46,0,.84-.38.84-.84v-27.16c0-.46-.38-.84-.84-.84ZM43.19,17.7v25.48h-20.36v-25.48h20.36ZM25.85,30.71c-.22-.41-.07-.92.33-1.14l12.85-7.05c.41-.22.92-.07,1.14.33.11.2.13.42.07.64-.06.22-.21.39-.4.5l-12.85,7.05c-.12.07-.26.1-.4.1-.31,0-.59-.17-.74-.43ZM26.58,38.47c.14,0,.28-.04.4-.1l12.85-7.05c.41-.22.55-.73.33-1.14-.22-.41-.74-.55-1.14-.33l-12.85,7.05c-.41.22-.55.73-.33,1.14.15.27.43.43.74.43ZM26.58,38.05h0s0,0,0,0ZM26.7,38.02h0s0,0,0,0Z"/>
                            </svg>
                        </button>
                    </div>
                    <button class="color-select-close" onClick="showColorSelector();">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40">
                            <line class="cls-2" x1="30" y1="10" x2="10" y2="30"/>
                            <line class="cls-2" x1="30" y1="30" x2="10" y2="10"/>
                        </svg>
                    </button>
                    <div id="color-select-roof" class="color-selector">
                        <h2 id="color-select-title">Techo</h2>
                        <?php
                            $colors = [
                                ['name' => 'Blanco', 'class' => 'white'],
                                ['name' => 'Rosa Mexicano', 'class' => 'pink'],
                                ['name' => 'Azul Cobalto', 'class' => 'cobalt'],
                                ['name' => 'Mostaza', 'class' => 'mustard'],
                                ['name' => 'Terracotta', 'class' => 'terracota'],
                                ['name' => 'Tezontle', 'class' => 'tezontle'],
                                ['name' => 'Gris Cantera', 'class' => 'cantera'],
                                ['name' => 'Gris Concreto', 'class' => 'concrete'],
                                ['name' => 'Rojo', 'class' => 'red'],
                                ['name' => 'Aqua', 'class' => 'teal'],
                            ];
                        ?>
                        <div class="color-selector-grid">
                            <?php foreach($colors as $color): ?>
                                <div class="color-thumb">
                                    <button class="color-element <?php echo $color['class']; ?>" data-color="<?php echo $color['class']; ?>" onClick="changeColor(this);"></button>
                                    <h4 class="color-name"><?php echo $color['name']; ?></h4>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div id="color-select-walls" class="color-selector">
                        <h2 id="color-select-title">Paredes</h2>
                        <?php
                            $colors = [
                                ['name' => 'Blanco', 'class' => 'white'],
                                ['name' => 'Rosa Mexicano', 'class' => 'pink'],
                                ['name' => 'Azul Cobalto', 'class' => 'cobalt'],
                                ['name' => 'Mostaza', 'class' => 'mustard'],
                                ['name' => 'Terracotta', 'class' => 'terracota'],
                                ['name' => 'Tezontle', 'class' => 'tezontle'],
                                ['name' => 'Gris Cantera', 'class' => 'cantera'],
                                ['name' => 'Gris Concreto', 'class' => 'concrete'],
                                ['name' => 'Rojo', 'class' => 'red'],
                                ['name' => 'Aqua', 'class' => 'teal'],
                            ];
                        ?>
                        <div class="color-selector-grid">
                            <?php foreach($colors as $color): ?>
                                <div class="color-thumb">
                                    <button class="color-element <?php echo $color['class']; ?>" data-color="<?php echo $color['class']; ?>" onClick="changeColor(this);"></button>
                                    <h4 class="color-name"><?php echo $color['name']; ?></h4>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div id="color-select-windows" class="color-selector">
                        <h2 id="color-select-title">Ventanas</h2>
                        <?php
                            $colors = [
                                ['name' => 'Blanco', 'class' => 'white'],
                                ['name' => 'Caoba', 'class' => 'mahogany'],
                                ['name' => 'Roble', 'class' => 'golden-oak'],
                                ['name' => 'Gris Antracita', 'class' => 'bronze'],
                                ['name' => 'Nogal', 'class' => 'nogal'],
                                ['name' => 'Negro', 'class' => 'black'],
                                ['name' => 'Café Decor', 'class' => 'brown'],
                                ['name' => 'Fresno Americano', 'class' => 'shiefeld'],
                                ['name' => 'Plata', 'class' => 'silver'],
                                ['name' => 'Beige', 'class' => 'beige'],
                            ];
                        ?>
                        <div class="color-selector-grid">
                            <?php foreach($colors as $color): ?>
                                <div class="color-thumb">
                                    <button class="color-element <?php echo $color['class']; ?>" data-color="<?php echo $color['class']; ?>" onClick="changeColor(this);"></button>
                                    <h4 class="color-name"><?php echo $color['name']; ?></h4>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="main-image house-1">
                    <div class="action-buttons">
                        <button class="action-button roof" onClick="showColorSelector('roof');"><img src="assets/images/action-button.svg" alt="Action Button"></button>
                        <button class="action-button walls" onClick="showColorSelector('walls');"><img src="assets/images/action-button.svg" alt="Action Button"></button>
                        <button class="action-button window" onClick="showColorSelector('windows');"><img src="assets/images/action-button.svg" alt="Action Button"></button>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1920 1080">
                        <image class="house-image" id="house-image" href="assets/images/kbuilder-1.jpg" x="0" y="0" width="100%" height="100%" preserveAspectRatio="xMidYMid slice" />
                        
                        <polygon class="roof-color" points="1601.6 229.83 1294.86 134.88 1157.67 175.76 726.89 60.25 282.84 271.14 283.59 279.22 287.52 279.51 729.58 75.32 1156.73 185.98 1293.92 147.58 1595.4 239.1 1601.23 239.1 1601.6 229.83"/>
                        <polygon class="roof-color" points="1596 281.34 1595.4 239.1 1596.14 290.73 1695.12 318.17 1700.12 318.63 1700.12 310.05 1596 281.34"/>

                        <path class="walls-color" d="M1696.07,887.45l.03-570.01-99.96-27.71-.74-51.63-301.48-91.52-137.19,38.4-427.15-110.66-442.06,204.19v621.42l443.67,111.51,162.23-25.22,43.9-4.7,1.66-1.92,126.2-18,84.71-14.04,8.06-1.74,18.21,2.5,118.27,19.28,406.06-75.58v-3.41l-4.43-1.15ZM353.77,590.55l13.94-.05-.07-227.67,62.92-18.55v244.76l-4.62-.05-72.17,4.99v-3.42ZM430.59,922.87l-61.08-11.24v-226.12l60.48.47.6,236.89ZM647.58,571.86l-2.42,3.22-98.87,5.64v-4.84l16.93-.81.27-273.77,86.51-27.4.81,297.95h-3.22ZM893.43,979.59v-288.76l160.59-2.03.25,266.72-160.84,24.06ZM1068.75,580.56h-5.97l-.27,2.44-168.31-8.42-.27-21.72-1.63-276.9,161.8,30.95.27,269.57,9.77.05,4.62.22v3.8ZM1542.93,916.72l-232.62,40.76.31,1.53-18.01,2.04-106.84-16.26.8-77.52-.61-179.91,108.19.92,248.87-5.21-.08,233.64ZM1558.08,590.99h-2.92l-1.18,1.76-259.31-13.38-122.75,4.26v-3.97l3.38-1.76,10.88.15.52-259.36,107.83-20.67,248.59,53.27v236.19h14.95v3.51ZM1596.29,398.91l26.69,5.3v194.39l9.89.28.17.67h4.71v2.3h-4.92v1.14l-36.54-1.33v-202.76ZM1637.85,822.07h-1.46l-1.31-1.6-2.63.29.15,1.31-35.91,3.8v-144.96h26.13v138.52l15.37-.04-.34,2.69Z"/>
                        
                        <g id="Layer_4" data-name="Layer 4">
                            <path class="windows-color" d="M416.86,498.93l1.78-.59-.34-133.61,12.27-4.05v-17.89l-62.92,20.03.07,227.67-13.94.05v3.42l72.17-4.99,4.62-1.43v-14.57l-13.7,1.14v-75.19ZM380.4,577.02v-200.32l23.05-7.76.7,206.15-23.75,1.93Z"/>
                            <path class="windows-color" d="M627.45,464.65l1.83-1.32.23-162.38,20.55-6.37-.06-20.67-86.51,27.4-.27,273.77-16.93.81v4.84l98.87-5.64,2.42-3.22h3.22v-17.52l-22.21,2-1.14-91.69ZM585.29,560.51l-.18-89.22,4.52-.47.31-158.22,20.32-6.08v252.59l-24.98,1.41Z"/>
                            <path class="windows-color" d="M1064.13,576.54l-9.77-.05-.27-269.57-161.8-30.95.12,20.14,63.72,11.54v157.22h1.92v92.64l-64.13-4.64.27,21.72,168.31,8.42.27-2.44h5.97v-3.8l-4.62-.22ZM978.44,558.59l-.8-247.61,59.26,10.8,1.24,240.03-59.7-3.22Z"/>
                            <path class="windows-color" d="M1543.13,587.48v-236.19l-248.59-53.27-107.83,20.67-.52,259.36-10.88-.15-3.38,1.76v3.97l122.75-4.26,259.31,13.38,1.18-1.76h2.92v-3.51h-14.95ZM1266,566.29l-68.83,3.98-.58-241.58,69.4-12.99v250.59ZM1313.4,567.43l.86-254.12,114.38,24.71,1.49,234.69-116.73-5.28ZM1534.75,578.76l-97.51-6.05v-233.49l98.13,21.03-.62,218.52Z"/>
                            <polygon class="windows-color" points="1633.04 599.55 1632.87 598.88 1622.98 598.6 1622.98 404.21 1596.29 398.91 1596.29 411.4 1610.3 414.59 1610.69 587.16 1596.29 587.16 1596.29 601.67 1632.83 602.99 1632.83 601.86 1637.75 601.86 1637.75 599.55 1633.04 599.55"/>
                            <polygon class="windows-color" points="1622.81 819.42 1622.81 680.9 1596.68 680.9 1596.68 689.88 1611.23 689.88 1611.23 809.27 1596.68 811.04 1596.68 825.87 1632.59 822.07 1632.44 820.76 1635.07 820.46 1636.39 822.07 1637.85 822.07 1638.18 819.38 1622.81 819.42"/>
                            <path class="windows-color" d="M1543,683.08l-248.87,5.21-108.19-.92.61,179.91-.8,77.52,106.84,16.26,18.01-2.04-.31-1.53,232.62-40.76.08-233.64ZM1197.5,935.73v-237.17h66.71l5.51,247.27-72.22-10.1ZM1427.02,924.41l-109.86,18.67v-233.81l109.86-3.06v218.2ZM1524.03,905.44l-83.85,14.69v-215.14l85.08-1.84-1.22,202.29Z"/>
                            <path class="windows-color" d="M1054.02,688.81l-160.59,2.03v15.2l64.2-1.04-1.32,249.76-62.88,9.57v15.27l160.84-24.06-.25-266.72ZM977.74,953.16l1.59-248.17,57.68-1.59.79,239.68-60.06,10.08Z"/>
                            <path class="windows-color" d="M1054.02,688.81l-160.59,2.03v15.2l64.2-1.04-1.32,249.76-62.88,9.57v15.27l160.84-24.06-.25-266.72ZM977.74,953.16l1.59-248.17,57.68-1.59.79,239.68-60.06,10.08Z"/>
                            <path class="windows-color" d="M417.08,908.76v-210.14l12.93-.1-.03-12.54-60.48-.47v226.12l61.08,11.24v-11.03l-13.5-3.08ZM402.94,905.34l-20.95-4.92-.3-202.64,21.55-.3-.3,207.86Z"/>
                        </g>
                    </svg>                     
                </div>
            </div>
        </main>
        <footer>
            <p>&copy; 2024 K-Builder. All rights reserved.</p>
        </footer>
    </body>
</html>