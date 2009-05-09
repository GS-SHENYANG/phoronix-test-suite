<?php

/*
	Phoronix Test Suite
	URLs: http://www.phoronix.com, http://www.phoronix-test-suite.com/
	Copyright (C) 2009, Phoronix Media
	Copyright (C) 2009, Michael Larabel

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 3 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

class list_unsupported_tests implements pts_option_interface
{
	public static function run($r)
	{
		echo pts_string_header("Phoronix Test Suite - Unsupported Tests");
		foreach(pts_available_tests_array() as $identifier)
		{
			if(!pts_test_supported($identifier))
			{
				echo new pts_test_profile_details($identifier);
			}
		}
		echo "\n";
	}
}

?>
