<?php
    $sidebar = isset($sidebar) ? (array) $sidebar : [];

    $render_item = function(array $item) {
        if (isset($item['visible']) && !$item['visible']) {
            return;
        }
?>
        <li class="nav-item">
            <a href="<?php echo $item['url']; ?>" class="nav-link <?php echo !empty($item['active']) ? 'active' : ''; ?>">
                <i class="nav-icon <?php echo $item['icon']; ?>"></i>
                <p><?php echo $item['label']; ?></p>
            </a>
        </li>
<?php
    };
?>
<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <?php foreach ($sidebar as $node): ?>
            <?php if (isset($node['visible']) && !$node['visible']) { continue; } ?>

            <?php if ($node['type'] === 'header'): ?>
        <li class="nav-header"><?php echo $node['label']; ?></li>
            <?php elseif ($node['type'] === 'item'): ?>
                <?php $render_item($node); ?>
            <?php elseif ($node['type'] === 'tree'): ?>
        <li class="nav-item has-treeview <?php echo !empty($node['open']) ? 'menu-open' : ''; ?>">
            <a href="#" class="nav-link <?php echo !empty($node['active']) ? 'active' : ''; ?>">
                <i class="nav-icon <?php echo $node['icon']; ?>"></i>
                <p>
                    <?php echo $node['label']; ?>
                    <i class="right <?php echo isset($node['right_icon']) ? $node['right_icon'] : 'fas fa-angle-left'; ?>"></i>
                </p>
            </a>
            <ul class="nav nav-treeview"<?php echo !empty($node['force_display_style']) ? ' style="display: '.(!empty($node['open']) ? 'block' : 'none').';"' : ''; ?>>
                <?php foreach ((array) $node['children'] as $child): ?>
                    <?php if (isset($child['visible']) && !$child['visible']) { continue; } ?>
                <li class="nav-item">
                    <a href="<?php echo $child['url']; ?>" class="nav-link <?php echo !empty($child['active']) ? 'active' : ''; ?>">
                        <i class="<?php echo $child['icon']; ?> nav-icon"></i>
                        <p><?php echo $child['label']; ?></p>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>
</nav>
