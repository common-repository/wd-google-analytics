<style>
    #gawd_logs_tb {
        width: 97%;
        /*text-align: center;*/
    }

    #gawd_logs_tb td {
        border: 1px solid;
        padding: 2px 0px 2px 7px;
    }

    #gawd_logs_tb td:nth-child(1) {
        width: 80%;
    }

    #gawd_logs_tb td:nth-child(2) {
        width: 15%;
    }

    #gawd_logs_tb td:nth-child(3) {
        width: 2%;
    }
</style>
<table id="gawd_logs_tb">
    <thead>
    <th>Fail</th>
    <th>Log</th>
    <th>Date</th>
    </thead>
    <tbody>
    <?php foreach($logs as $log) { ?>
        <tr>
            <td><?php echo $log['log']; ?></td>
            <td><?php echo $log['date']; ?></td>
            <td><?php echo $log['fail'] ? '1' : "0"; ?></td>
        </tr>
    <?php } ?>
    </tbody>
</table>