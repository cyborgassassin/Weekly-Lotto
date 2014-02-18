<?php
$date = GetConfigValue("lottoDateRun", "weekly_lottery");

$diff = explode(',',GetConfigValue("lottoWinDifficulty","weekly_lottery"));
$setting = GetConfigValue("lottoRevolve","weekly_lottery");
$rand = mt_rand(1,$diff[0]);
/* is today the day your paying out?*/
if (date("l") == ucfirst($date))
{
    $players = array(); //Will hold the players in the lottery.
    $result  = $db->Execute('SELECT Uid, amount FROM weekly_lottery');
    while (!$result->EOF)
    {
        for ($i = 0; $i < $result->fields[1]; $i++) //Add the player to the array, for as many times as they have bought a ticket.
            $players[] = $result->fields[0];
        $result->MoveNext();
    }

    if (!empty($players))
    {
        shuffle($players); //Shuffle the array containing players.
        $winnerKey = array_rand($players); //Select a random key from the players array.
		//Todo: Add option to not allow revolving lotto
		if($setting === "on")
		{
        	if($diff[1] != $rand)
        	{
            	$db->Execute("insert into weekly_lottery_winners (userid,amount) values (?,?)",0,0);
            	$db->Execute('TRUNCATE TABLE weekly_lottery');
            	return;
			}
		}
        $winner    = $players[$winnerKey]; //Get the id of the winner using the random key.

        $getName = $db->Execute('SELECT username FROM users WHERE ( id = ? )', $winner); //Get the name of the winner.
        if (!$getName->EOF && $getName->FieldCount() == 1)
            $name = $getName->fields[0];
        else
            $name = 'Hidden Player'; //Just incase.
        $getName->Close();

        //The below is pretty much yours.

        $jackpotvalue = GetConfigValue('lottoJackpot');
        $stats        = UserStat::LoadStats($winner);
        $stats['!Currency']->value += $jackpotvalue;
        UserStat::SaveStats($stats, $winner);

        if (function_exists('SendChatLine'))
            SendChatLine(Translate('User %s has won the lotto.', $name));
        if (function_exists('SendMessage'))
            SendMessage($winner, 'Weekly Lottery', Translate('Congrats %s!!! You have just won the weekly lottery with a jackpot of %s !Currency', $name, $jackpotvalue), 1);
        $insert = $db->Execute("insert into weekly_lottery_winners (userid,amount) values (?,?)",$winner,$jackpotvalue);
    }

    //Reset lottery.
    $jackpot = GetConfigValue('lottoJackStart', 'weekly_lottery');
    SetConfigValue('lottoJackpot', $jackpot, 'weekly_lottery');
    $db->Execute('TRUNCATE TABLE weekly_lottery');

}