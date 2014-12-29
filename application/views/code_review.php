<html>
    <head>
        <title>Code Review Output</title>
        <link rel="stylesheet" type="text/css" href="http://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/2.1.1/css/bootstrap.min.css" />
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

                    <?php
                    if (is_array($controller["filename"]) || $controller["filename"] === 'index.html') {
                        continue; // This is a directory not a file or an index.html file
                    }
                    ?>
                    <tr>
                        <td><?php echo $controller["filename"]; ?></td>
                        <td><?php echo byte_format($controller["info"]["size"]); ?></td>
                        <td><?php echo date('d M Y', $controller["info"]["date"]); ?></td>
                        <td>
                            <ol>

                                 <?php foreach ($controller["functions_with_usage"] as $function => $usage_array): ?>
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

                    <?php
                    if (is_array($model["filename"]) || $model["filename"] === 'index.html') {
                        continue; // This is a directory not a file or an index.html file
                    }
                    ?>
                    <tr>
                        <td><?php echo $model["filename"]; ?></td>
                        <td><?php echo byte_format($model["info"]["size"]); ?></td>
                        <td><?php echo date('d M Y', $model["info"]["date"]); ?></td>
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
