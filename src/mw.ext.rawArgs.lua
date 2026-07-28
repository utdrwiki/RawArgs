local rawArgs = {}
local php

--- Get a table of all raw arguments to the current #invoke call, keyed the
-- same way frame.args is (numbers for positional args, strings for named).
-- @return table
function rawArgs.get()
	return php.get()
end

--- Get a table of all raw arguments to the parent frame of the #invoke call,
-- keyed the same way frame.args is (numbers for positional args, strings for
-- named).
-- @return table
function rawArgs.getParent()
	return php.getParent()
end

--- Set up the base environment. The PHP host calls this function after any
-- necessary host-side initialisation has been done.
function rawArgs.setupInterface( options )
	rawArgs.setupInterface = nil
	php = mw_interface
	mw_interface = nil

	mw = mw or {}
	mw.ext = mw.ext or {}
	mw.ext.rawArgs = rawArgs

	package.loaded['mw.ext.rawArgs'] = rawArgs
end

return rawArgs
