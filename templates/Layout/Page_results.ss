
<%--<style>
	.book-description {
		display:none!important;
	}
</style>--%>

XXXX

$StartContainer()
	$StartRow()
		$StartCol('overflow-hiddenx')
			$StartSection('px-3 ml-2')



					<div id="Content" class="searchResults">
						
						<div class="text-right ml-auto" style="max-width: 700px;">
							<h3 class="pagetitle themecolor text-uppercase">$Title</h3>
						</div>

						<br><br>
						$SearchForm
						<br>
						

						<%--<% if $Query %>
							<p class="searchQuery">You searched for &quot;{$Query}&quot;</p>
						<% end_if %>--%>
							
						<% if $Query && $Results.Count() >= 1 %>
							<h3 class="search-result-message themecolor"><%t SearchResults.YesResult "Your search for &quot;{query}&quot; returned {count} results:" query=$cleanQuery($Query) count=$Results.Count() %></h3>
							<hr>
							<br>
						<% end_if %>
							
							<%--$Results.debug--%>

						<% if $Results %>
						<ul id="SearchResults">
							<% loop $Results %>
							<li class="search-resullt-li">
								<h3 class="pagetitle themecolor text-uppercase" style="margin-leftx: 1em; text-indentx: -1em;">
									<a href="$Link">
										<% if $MenuTitle %>
											<%--&bull;--%> $MenuTitle
										<% else %>
											<%--&bull;--%> $RTitle.RAW
										<% end_if %>
									</a>
								</h3>
									<% if $SubTitle %>
										<a href="$Link">
											<p class="p_20 ml-n4 themecolor text-justify">$SubTitle <br></p>
										</a>
									<% end_if %>
											
								<% if $RContent %>
									<%--MYCONTENT<br>--%>
									<a href="$Link" class="search-result-content">
										<p class="p_18 ml-0 ml-n4 themecolorx text-secondary text-justify">$RContent.RAW <%--.MyLimitWordCount(60).RAW--%></p>
									</a>
									
								<% end_if %>
									
									<%--<h3 class="object-titlexx pt-2 pb-0 mb-0">
										<a class="readMoreLink" href="$Link" title="Read more about &quot;{$Title}&quot;">Lesen Sie mehr von: &quot;{$Title}&quot;...</a>
									</h3>--%>
								
							</li>
							<hr style="margin-left: -30px;">
							<% end_loop %>
						</ul>
						<% else %>
						<p><%t SearchResults.NoResult "Sorry, your search query did not return any results." query=$cleanQuery($Query)  %></p>
						<% end_if %>

						
							</div>				
							
			<br><br><br>
							
			$EndSection()
		$EndCol()
	$EndRow()

	$Top.StartRow('no-gutters pl-4 pr-3')				
		$Top.StartCol('text-center')
			$StartSection('px-3 ml-2')
							
			<div>
				
				<% if $Results.MoreThanOnePage %>

					<% if $Results.NotFirstPage %>
						<div class="d-inline-block"><a class="prev pr-4" href="$Results.PrevLink"><!--Prev--> <!-- << -->  <span class="pagination-backlink"></span></a></div>
					<% end_if %>
						
					<div class="d-inline-block mb-3">
						<p class="p_16 p-0 m-0">
						
							<% loop $Results.PaginationSummary %>
								<% if $CurrentBool %>
									<b class="px-1">$PageNum</b>
								<% else %>
									<% if $Link %>
										<a class="px-1" href="$Link">$PageNum</a>
									<% else %>
										...
									<% end_if %>
								<% end_if %>
							<% end_loop %>
						
						</p>
					</div>

					<% if $Results.NotLastPage %>
						<div class="d-inline-block"><a class="next pl-4" href="$Results.NextLink"><!--Next-->  <!-- >> -->  <span class="pagination-forwardlink"></span></a></div>
					<% end_if %>

				<% end_if %>
				
			</div>
							
							
					
											
					<br><br><br>
						
						
			$EndSection()
		$EndCol()
	$EndRow()
$EndContainer()
	
