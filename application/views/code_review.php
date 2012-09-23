<html>
    <head>
        <title>Code Review Output</title>
        <link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>css/bootstrap.css" />
    </head>
    <body>

        <h2>Controllers (<?php echo count($controllers); ?>)</h2>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>File name</th>
                    <th>Size</th>
                    <th>Updated</th>
                    <th>Functions</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($controllers as $controller): ?>

                    <tr>
                        <td><?php echo $controller["filename"]; ?></td>
                        <td><?php echo $controller["size"]["size"]; ?></td>
                        <td><?php echo $controller["size"]["date"]; ?></td>
                        <td>
                            <ol>
                                <?php foreach ($controller["functions"] as $function): ?>
                                    <li><?php echo $function; ?></li>
                                <?php endforeach; ?>
                            </ol>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>

        <h2>Models (<?php echo count($models); ?>)</h2>

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>File name</th>
                    <th>Size</th>
                    <th>Updated</th>
                    <th>Functions</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($models as $model): ?>

                    <tr>
                        <td><?php echo $model["filename"]; ?></td>
                        <td><?php echo $model["size"]["size"]; ?></td>
                        <td><?php echo $model["size"]["date"]; ?></td>
                        <td>
                            <ol>
                                <?php foreach ($model["functions_with_usage"] as $function => $usage_array): ?>
                                    <li><?php echo $function; ?>()</li>
                                    <ul>
                                        <?php if (is_array($usage_array['usage']) && !empty($usage_array['usage'])): ?>

                                            <?php foreach ($usage_array['usage'] as $uses): ?>
                                                <li><?php echo $uses; ?></li>
                                            <?php endforeach; ?>

                                        <?php else: ?>
                                            <li><span class="label label-warning">Appears to be unused</span></li>
                                        <?php endif; ?>
                                    </ul>
                                <?php endforeach; ?>
                            </ol>
                        </td>
                    </tr>

                <?php endforeach; ?>
            </tbody>
        </table>

    </body>
</html>