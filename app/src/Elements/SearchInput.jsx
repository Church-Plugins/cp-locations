import InputBase from '@mui/material/InputBase';
import { Search } from '@mui/icons-material';
import { useEffect } from 'react';

export default function SearchInput({
  onValueChange,
  initialValue = "",
  // "short", "full"
  width = "short",
}) {
 
  return (
    <InputBase
      className="searchInput__root"
      placeholder="Enter your zip code"
      defaultValue={initialValue}
      startAdornment={<Search />}
      inputProps={{pattern: '[0-9]{5}', maxLength: '5'}}
      onChange={e => onValueChange(e.target.value)}
    />
  );
}
