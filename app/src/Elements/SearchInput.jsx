import InputBase from '@mui/material/InputBase';
import Box from '@mui/material/Box';
import Autocomplete from '@mui/material/Autocomplete';
import TextField from '@mui/material/TextField';
import debounce from '@mui/material/utils/debounce';
import Search from '@mui/icons-material/Search';
import LocationOn from '@mui/icons-material/LocationOn';
import InputAdornment from '@mui/material/InputAdornment';
import { useEffect, useMemo, useState } from 'react';
import { AddressAutofillCore, SessionToken } from '@mapbox/search-js-core';


export default function SearchInput({
  onValueChange,
  // "short", "full"
  width = "small",
}) {
  const [value, setValue] = useState(null);
  const [inputValue, setInputValue] = useState('');
	const [autofill, setAutofill] = useState(() => {
		return new AddressAutofillCore({
			accessToken: window.cplocVars.mapboxAccessToken,
		})
	});
	const [sessionToken, setSessionToken] = useState(() => new SessionToken())
  const [suggestions, setSuggestions] = useState([])
  const signal = useMemo(() => new AbortController().signal, []);

	const urlParams = new URLSearchParams(window.location.search);

  const addressSearch = async (value) => {
    if(!value) {
      setSuggestions([])
      return
    }

    try {
      const results = await autofill.suggest(value, { sessionToken, signal })
      setSuggestions(results.suggestions)
    } catch (error) {
      setSuggestions([])
      return
    }
  }

  const retrieveAddress = async (value) => {
    if(!value) {
      return
    }

    const result = await autofill.retrieve(value, { sessionToken })
    onValueChange(result)
  }

  const handleInputChange = useMemo(() => debounce(addressSearch, 100), []);

  return (
    <Autocomplete
      value={value}
      getOptionLabel={(option) => option.place_name}
      onChange={(e, newValue) => {
        setValue(newValue)
        setSuggestions(newValue ? [newValue, ...suggestions] : suggestions)
        retrieveAddress(newValue)
      }}
      inputValue={inputValue}
      onInputChange={(e, newValue) => {
        setInputValue(newValue)
        handleInputChange(newValue)
      }}
      className='searchInput__root'
      renderInput={(params) => (
        <TextField
          {...params}
          label="Search"
          InputProps={{
            ...params.InputProps,
            startAdornment: (
              <InputAdornment position="start">
                <Search />
              </InputAdornment>
            )
          }}
        />
      )}
      renderOption={(props, option) => (
        <li {...props}>
          {console.log(option)}
          <Box display="flex" alignItems="center" gap={2}>
            <LocationOn sx={{ color: 'text.secondary' }} />
            <span>{option.place_name}</span>
          </Box>
        </li>
      )}
      options={suggestions}
      filterOptions={x => x}
      includeInputInList
      size={width}
      sx={{ width: 'min(60%, 300px)' }}
    />
  )

  // return (
  //   <InputBase
  //     className="searchInput__root"
  //     placeholder="Enter your zip code"
  //     defaultValue={urlParams.get('s')}
  //     startAdornment={<Search />}
  //     inputProps={{pattern: '[0-9]+', maxLength: '5'}}
  //     onChange={e => onValueChange(e.target.value)}
  //   />
  // );
}
