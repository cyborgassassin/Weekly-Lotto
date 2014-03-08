<?php

//Load up all them config values
$cost = GetConfigValue("lottoTicketPrice","weekly_lottery");
//The price per lotto ticket
$jackpot = GetConfigValue("lottoJackpot","weekly_lottery");
//The actual Jackpot Payout $$$$
$perc = GetConfigValue("lottoJackPerc","weekly_lottery") / 100;
//The amount to be placed into the jackpot
$date = ucfirst(GetConfigValue("lottoDateRun","weekly_lottery"));
//Day of the week the lotto will run
$rticks = 0; //Default rticks.
$result = $db->Execute ( 'SELECT amount FROM weekly_lottery WHERE ( Uid = ? )', $userId );
if ( !$result->EOF ) //Make sure we have a result.
    $rticks = $result->fields[0]; //Set the rticks to how many this user has.

$max = GetConfigValue ( "lottoMaxTickets", "weekly_lottery" ); //Check max amount.
if ( $max != 0 )
    $left = $max - $rticks; //If max is not set to 0, then remove rticks from max and see what we're left with.
else
    $max = "Unlimited amount of"; //For the translation


TableHeader(Translate("Welcome to the %s Lottery!! Next run is this coming %s", $gameName, $date));
if (isset($_POST['purch']) && $_POST['purch'] >= 1)
{
    $bought = filter_input(INPUT_POST,'purch',FILTER_VALIDATE_INT)+0;
    $price  = $bought * $cost;
    $error = false; //Just to make it cleaner.

    if ( isset ( $left ) ) //If max is not unlimited.
    {

        if ( $left <= 0 || $bought > $left ) //If left is less/equal to 0 or the amount bought is more than left, then error.
        {
            $error = true;
            ErrorMessage ( Translate ( "You can't buy more than %d lottery tickets. <a href='index.php?p=weekly_lottery'>Go Back</a>", $max ) );
        }
    }

    if ( $userStats["!Currency"]->value < $price ) //If the currency is less than the total error.
    {
        $error = true;
        ErrorMessage ( Translate ( "You don't have enough !Currency to buy %d ticket(s).", $bought ) );
    }

    if ( !$error ) //If no error we can continue with the purchase.
    {
        $new_jackpot = $jackpot + $price * $perc; //Yours.
        ResultMessage ( Translate ( "You have bought %d lottery ticket(s).", $bought ) );
        echo "<script>setTimeout(\"document.location='index.php?p=weekly_lottery';\",1000);</script>";


        if ( $rticks ) //If rtricks is not 0, then we already have a record in the db, lets update it.
            $db->Execute ( 'UPDATE weekly_lottery SET amount = amount + ? WHERE ( Uid = ? )', $bought, $userId );
        else //We have no record, which means this is a new purchase, so we'll insert a record.
            $db->Execute ( 'INSERT INTO weekly_lottery ( Uid, amount ) VALUES ( ?, ? )', $userId, $bought );

        $userStats["!Currency"]->value -= $price;

        SetConfigValue("lottoJackpot", $new_jackpot, "weekly_lottery");
    }
}
$bought = $db->Execute("SELECT SUM(amount) FROM weekly_lottery");
echo "<table class='plainTable'>";
echo "<tr class='titleLine'>";
echo "<td>Your " . Translate("Tickets") . ":</td>";
echo "<td>" . Translate("Tickets") . " Avaialble:</td>";
echo "<td>Mamimum " . Translate("Tickets") . ":</td>";
echo "<td>" . Translate("Ticket") . " Price:</td>";
echo "<td>Total " . Translate("Tickets") . " Purchased:</td>";
echo "<td>" . Translate("Jackpot") . ":</td></tr>";
echo "<tr class='oddLine'>";
echo "<td>".FormatNumber($rticks)."</td>";
echo "<td>".FormatNumber($left)."</td>";
echo "<td>".FormatNumber($max)."</td>";
echo "<td>".FormatNumber($cost)."</td>";
echo "<td>".FormatNumber($bought->fields[0])."</td>";
echo "<td>".FormatNumber($jackpot).Translate(" !Currency")."</td>";

echo "</tr></table>";
echo "<form method='post' name='purch'>";
echo "<b>Amount Of ".Translate("Tickets").":</b></td>";
echo "<td><input type = 'text' name = 'purch' value = '1' /></td></tr>";
echo "</form>";

ButtonArea();
SubmitButton("Buy Ticket", "purch");
LinkButton("Cancel", "index.php");
EndButtonArea();
TableFooter();
$limiter = 10;
$result = $db->Execute("select id, date, userid, amount from weekly_lottery_winners order by date desc limit ?",$limiter);
$count = $db->Execute("select count(*) as num from weekly_lottery_winners limit ?",$limiter);
if($count->fields[0] < $limiter)
    $amount = $count->fields[0];
else
    $amount = $limiter;
$count->Close();
TableHeader(Translate("Previous %s Winners",$amount), true, true);
echo "<table class='plainTable'>";
echo "<tr class='titleLine'>";
echo "<td width='10%'>&nbsp;</td>";
echo "<td width='30%'>" . Translate("Date") . "</td>";
echo "<td width='30%'>" . Translate("Winner") . "</td>";
echo "<td width='30%'>" . Translate("Amount") . "</td>";
echo "</tr>";
$row = 0;
if(! $result->EOF)
{
    foreach($result as $winner)
    {
        if($winner->userid == 0)
	    $name["username"] = "No Winner";
        else
	    $name = $db->LoadData("select username from users where id = ?",$winner->userid);
        if ($row % 2 == 0)
            echo "<tr class='evenLine'>";
        else
            echo "<tr class='oddLine'>";
        $row ++;
        echo "<td>". $row .".</td>";
        echo "<td>" . FormatShortDate($winner->date) . "</td>";
        echo "<td>" . htmlentities($name["username"]) . "</td>";
        echo "<td>" . FormatNumber($winner->amount) . Translate(" !Currency");
        echo "</tr>";
    }
}

echo "</table>";
TableFooter();
